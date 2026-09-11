<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpChannel;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpDeliveryException;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpThrottledException;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\SmsManager;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected SettingsRepository $settings,
        protected SmsManager $sms,
    ) {}

    public function create(): View
    {
        return view('auth.login', [
            'countries' => config('endless.phone.countries'),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:160'],
            'country_code' => ['nullable', 'string', 'max:5'],
        ], [], ['identifier' => 'אימייל או טלפון']);

        $raw = trim((string) $request->input('identifier'));
        $isPhone = PhoneNumber::looksLikePhone($raw);

        if ($isPhone) {
            $phone = PhoneNumber::normalize($raw, $request->input('country_code'));
            $user = $phone ? User::query()->where('phone', $phone)->first() : null;
        } else {
            $user = User::query()->where('email', mb_strtolower($raw))->first();
        }

        if (! $user) {
            return back()->withInput()->withErrors(['identifier' => 'לא מצאנו חשבון עם הפרטים האלה. בדקו את הפרטים או צרו עמוד הנצחה חדש.']);
        }

        $channel = $this->pickChannel($user, $isPhone);

        try {
            $this->issueFor($user, $channel);
        } catch (OtpThrottledException $e) {
            return back()->withInput()->withErrors(['identifier' => $e->getMessage()]);
        }

        return redirect()->route('login.verify');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $identifier = (string) $request->session()->get('login.identifier');
        $channel = OtpChannel::from($request->session()->get('login.channel', 'email'));

        return view('auth.verify', [
            'purpose' => 'login',
            'channel' => $channel,
            'identifierMasked' => $this->mask($identifier),
            'resendIn' => $this->otp->secondsUntilResend($identifier, 'login'),
            'alternateChannel' => $channel === OtpChannel::Sms ? ($user->email ? OtpChannel::Email : null) : ($user->phone ? OtpChannel::Sms : null),
            'verifyRoute' => route('login.verify.store'),
            'resendRoute' => route('login.resend'),
            'devCode' => app()->environment('local') ? config('endless.otp.dev_code') : null,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'digits:'.config('endless.otp.length', 6)]], [], ['code' => 'קוד האימות']);

        $otp = $this->otp->verify($request->session()->get('login.identifier'), $request->input('code'), 'login');
        if (! $otp) {
            return back()->withErrors(['code' => 'הקוד שגוי או שפג תוקפו. נסו שוב או בקשו קוד חדש.']);
        }

        $channel = $request->session()->get('login.channel');
        $user->forceFill([
            $channel === OtpChannel::Sms->value ? 'phone_verified_at' : 'email_verified_at' => now(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget(['login.user_id', 'login.identifier', 'login.channel']);

        return redirect()->intended($user->is_admin && ! $user->primaryMemorial() ? route('admin.index') : route('dashboard.index'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $requested = $request->input('channel');
        $channel = in_array($requested, ['sms', 'email'], true)
            ? OtpChannel::from($requested)
            : OtpChannel::from($request->session()->get('login.channel', 'email'));

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

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /* ------------------------------------------------------------------ */

    protected function pickChannel(User $user, bool $typedPhone): OtpChannel
    {
        if ($typedPhone && $user->phone && $this->sms->isConfigured()) {
            return OtpChannel::Sms;
        }
        if (! $typedPhone && $user->email && ($this->settings->mailConfigured() || app()->environment(['local', 'testing']))) {
            return OtpChannel::Email;
        }

        return $this->otp->channelFor($user->email, $user->phone);
    }

    protected function issueFor(User $user, OtpChannel $channel): void
    {
        $identifier = $channel === OtpChannel::Sms ? $user->phone : $user->email;

        try {
            $this->otp->issue($identifier, $channel, 'login', $user);
        } catch (OtpDeliveryException) {
            $other = $channel === OtpChannel::Sms ? OtpChannel::Email : OtpChannel::Sms;
            $otherId = $other === OtpChannel::Sms ? $user->phone : $user->email;
            if (! $otherId) {
                throw new OtpThrottledException(0);
            }
            $channel = $other;
            $identifier = $otherId;
            $this->otp->issue($identifier, $channel, 'login', $user);
        }

        session([
            'login.user_id' => $user->id,
            'login.identifier' => $identifier,
            'login.channel' => $channel->value,
        ]);
    }

    protected function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.user_id');

        return $id ? User::find($id) : null;
    }

    protected function mask(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$name, $domain] = explode('@', $identifier, 2);

            return mb_substr($name, 0, 2).'•••@'.$domain;
        }

        return '•••'.substr($identifier, -4);
    }
}
