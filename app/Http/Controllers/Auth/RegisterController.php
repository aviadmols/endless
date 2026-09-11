<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Gender;
use App\Enums\OtpChannel;
use App\Enums\Religion;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\MemorialCreatedMail;
use App\Models\ActivityLog;
use App\Models\Memorial;
use App\Models\User;
use App\Services\Media\ImageProcessor;
use App\Services\Otp\OtpDeliveryException;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpThrottledException;
use App\Services\Settings\SettingsRepository;
use App\Support\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class RegisterController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected ImageProcessor $images,
        protected SettingsRepository $settings,
    ) {}

    public function create(): View
    {
        return view('auth.register', [
            'countries' => config('endless.phone.countries'),
            'religions' => Religion::options(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $email = $request->input('creator_email');
        $phone = $request->input('creator_phone_e164');

        $existing = User::query()->where('email', $email)->orWhere('phone', $phone)->first();
        if ($existing) {
            return redirect()->route('login')
                ->with('status', 'כבר קיים חשבון עם הפרטים האלה. התחברו כדי להמשיך לאזור האישי.')
                ->with('prefill', $existing->email ?: $existing->phone);
        }

        /** @var Memorial $memorial */
        $memorial = DB::transaction(function () use ($request, $email, $phone) {
            $user = User::create([
                'first_name' => $request->string('creator_first_name')->trim(),
                'last_name' => $request->string('creator_last_name')->trim(),
                'email' => $email,
                'phone' => $phone,
            ]);

            $gender = Gender::from($request->input('deceased_gender'));
            $bio = trim((string) $request->input('deceased_bio'));

            $memorial = Memorial::create([
                'user_id' => $user->id,
                'slug' => SlugGenerator::forMemorial($request->input('deceased_first_name'), $request->input('deceased_last_name')),
                'first_name' => $request->string('deceased_first_name')->trim(),
                'last_name' => $request->string('deceased_last_name')->trim() ?: null,
                'gender' => $gender,
                'subtitle' => $gender->defaultSubtitle(),
                'birth_date' => $request->input('deceased_birth_date') ?: null,
                'death_date' => $request->input('deceased_death_date'),
                'religion' => Religion::from($request->input('deceased_religion')),
                'biography' => $bio !== '' ? $this->paragraphs($bio) : null,
                'founder_name' => $user->name,
            ]);

            foreach ((array) $request->file('deceased_image', []) as $i => $file) {
                if (! $file) {
                    continue;
                }
                $stored = $this->images->store($file, "memorials/{$memorial->id}/gallery");
                $memorial->images()->create($stored + ['sort_order' => $i]);
                if ($i === 0) {
                    $portrait = $this->images->store($file, "memorials/{$memorial->id}/portrait");
                    $memorial->forceFill(['portrait_image_path' => $portrait['path']])->save();
                }
            }

            ActivityLog::record('memorial.created', $memorial, $user);

            return $memorial;
        });

        $user = $memorial->owner;
        $channel = $this->otp->channelFor($user->email, $user->phone);

        try {
            $this->issueFor($user, $channel);
        } catch (OtpThrottledException $e) {
            return back()->withInput()->withErrors(['otp' => $e->getMessage()]);
        }

        return redirect()->route('register.verify');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('register');
        }

        return view('auth.verify', $this->verifyContext($request, $user, 'register'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('register');
        }

        $request->validate(['code' => ['required', 'digits:'.config('endless.otp.length', 6)]], [], ['code' => 'קוד האימות']);

        $otp = $this->otp->verify($request->session()->get('register.identifier'), $request->input('code'), 'register');
        if (! $otp) {
            return back()->withErrors(['code' => 'הקוד שגוי או שפג תוקפו. נסו שוב או בקשו קוד חדש.']);
        }

        $channel = $request->session()->get('register.channel');
        $user->forceFill([
            $channel === OtpChannel::Sms->value ? 'phone_verified_at' : 'email_verified_at' => now(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget(['register.user_id', 'register.identifier', 'register.channel', 'register.preview']);

        $memorial = $user->primaryMemorial();
        if ($memorial && $user->email && ($this->settings->mailConfigured() || app()->environment(['local', 'testing']))) {
            try {
                Mail::to($user->email)->send(new MemorialCreatedMail($memorial));
            } catch (Throwable) {
                // Welcome mail is best-effort.
            }
        }

        return redirect()->route('dashboard.index')->with('memorial_created', true);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('register');
        }

        $requested = $request->input('channel');
        $channel = in_array($requested, ['sms', 'email'], true)
            ? OtpChannel::from($requested)
            : OtpChannel::from($request->session()->get('register.channel', 'email'));

        if (($channel === OtpChannel::Sms && ! $user->phone) || ($channel === OtpChannel::Email && ! $user->email)) {
            $channel = $this->otp->channelFor($user->email, $user->phone);
        }

        try {
            $this->issueFor($user, $channel);
        } catch (OtpThrottledException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'שלחנו קוד חדש.');
    }

    /* ------------------------------------------------------------------ */

    protected function issueFor(User $user, OtpChannel $channel): void
    {
        $identifier = $channel === OtpChannel::Sms ? $user->phone : $user->email;

        try {
            $this->otp->issue($identifier, $channel, 'register', $user);
        } catch (OtpDeliveryException $e) {
            // Fall back to the other channel when the first one is not deliverable.
            $other = $channel === OtpChannel::Sms ? OtpChannel::Email : OtpChannel::Sms;
            $otherId = $other === OtpChannel::Sms ? $user->phone : $user->email;
            if (! $otherId) {
                throw new OtpThrottledException(0);
            }
            $channel = $other;
            $identifier = $otherId;
            $this->otp->issue($identifier, $channel, 'register', $user);
        }

        session([
            'register.user_id' => $user->id,
            'register.identifier' => $identifier,
            'register.channel' => $channel->value,
            'register.preview' => $this->otp->isSimulated($channel) ? $this->otp->lastPlainCode : null,
        ]);
    }

    protected function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('register.user_id');

        return $id ? User::find($id) : null;
    }

    protected function verifyContext(Request $request, User $user, string $purpose): array
    {
        $identifier = (string) $request->session()->get("{$purpose}.identifier");
        $channel = OtpChannel::from($request->session()->get("{$purpose}.channel", 'email'));

        return [
            'purpose' => $purpose,
            'channel' => $channel,
            'identifierMasked' => $this->mask($identifier),
            'resendIn' => $this->otp->secondsUntilResend($identifier, $purpose),
            'alternateChannel' => $channel === OtpChannel::Sms ? ($user->email ? OtpChannel::Email : null) : ($user->phone ? OtpChannel::Sms : null),
            'verifyRoute' => route("{$purpose}.verify.store"),
            'resendRoute' => route("{$purpose}.resend"),
            'previewCode' => $request->session()->get("{$purpose}.preview"),
        ];
    }

    protected function mask(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$name, $domain] = explode('@', $identifier, 2);

            return mb_substr($name, 0, 2).'•••@'.$domain;
        }

        return '•••'.substr($identifier, -4);
    }

    protected function paragraphs(string $text): string
    {
        $parts = preg_split('/\r?\n\s*\r?\n|\r?\n/', trim($text)) ?: [];
        $html = '';
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $html .= '<p>'.e($p).'</p>'."\n";
            }
        }

        return trim($html);
    }
}
