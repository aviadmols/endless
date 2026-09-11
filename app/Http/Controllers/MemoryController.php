<?php

namespace App\Http\Controllers;

use App\Enums\MemoryStatus;
use App\Http\Requests\StoreMemoryRequest;
use App\Mail\NewMemoryMail;
use App\Models\ActivityLog;
use App\Models\Memorial;
use App\Models\Memory;
use App\Services\Html\HtmlSanitizer;
use App\Services\Media\MemoryMediaStore;
use App\Services\Settings\SettingsRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class MemoryController extends Controller
{
    public function __construct(
        protected HtmlSanitizer $sanitizer,
        protected MemoryMediaStore $mediaStore,
        protected SettingsRepository $settings,
    ) {}

    public function show(Request $request, Memorial $memorial, Memory $memory): View
    {
        $canSeeAll = $memorial->isOwnedBy($request->user()) || (bool) $request->user()?->is_admin;
        abort_unless($memory->is_approved || $canSeeAll, 404);

        $memory->load('media');

        return view('memories.show', [
            'memorial' => $memorial,
            'memory' => $memory,
            'others' => $memorial->approvedMemories()->with('media')->where('id', '!=', $memory->id)->limit(12)->get(),
        ] + $this->neighbours($memorial, $memory));
    }

    /**
     * Previous / next in the same order the feed uses (newest first), so visitors can
     * page through the memories instead of going back to the profile every time.
     *
     * @return array{previous: ?Memory, next: ?Memory, position: ?int, total: int}
     */
    protected function neighbours(Memorial $memorial, Memory $memory): array
    {
        $approved = fn (): Builder => Memory::query()
            ->where('memorial_id', $memorial->id)
            ->where('status', MemoryStatus::Approved->value);

        $total = $approved()->count();

        if (! $memory->is_approved) {
            return ['previous' => null, 'next' => null, 'position' => null, 'total' => $total];
        }

        // Newer than the current memory.
        $newer = fn (Builder $q) => $q->where(function (Builder $w) use ($memory) {
            $w->where('created_at', '>', $memory->created_at)
                ->orWhere(fn (Builder $tie) => $tie->where('created_at', $memory->created_at)->where('id', '>', $memory->id));
        });

        // Older than the current memory.
        $older = fn (Builder $q) => $q->where(function (Builder $w) use ($memory) {
            $w->where('created_at', '<', $memory->created_at)
                ->orWhere(fn (Builder $tie) => $tie->where('created_at', $memory->created_at)->where('id', '<', $memory->id));
        });

        return [
            // "Previous" walks back up towards the newest memory.
            'previous' => $newer($approved())->with('media')->orderBy('created_at')->orderBy('id')->first(),
            'next' => $older($approved())->with('media')->orderByDesc('created_at')->orderByDesc('id')->first(),
            'position' => $newer($approved())->count() + 1,
            'total' => $total,
        ];
    }

    /** Public "add a memory" form, reached through the owner's share link. */
    public function create(Request $request, Memorial $memorial, string $token): View
    {
        abort_unless(hash_equals($memorial->share_token, $token), 404);

        return view('memories.create', [
            'memorial' => $memorial,
            'token' => $token,
            'submitted' => (bool) $request->session()->get('memory_submitted'),
            'submittedApproved' => (bool) $request->session()->get('memory_approved'),
        ]);
    }

    public function store(StoreMemoryRequest $request, Memorial $memorial, string $token): RedirectResponse
    {
        abort_unless(hash_equals($memorial->share_token, $token), 404);

        $body = $this->sanitizer->clean($request->input('body'));
        $status = $memorial->require_approval ? MemoryStatus::Pending : MemoryStatus::Approved;

        /** @var Memory $memory */
        $memory = DB::transaction(function () use ($request, $memorial, $body, $status) {
            $memory = $memorial->memories()->create([
                'author_name' => $request->string('author_name')->trim(),
                'author_email' => $request->input('author_email') ? mb_strtolower(trim($request->input('author_email'))) : null,
                'author_phone' => $request->input('author_phone') ?: null,
                'title' => $request->input('title') ?: null,
                'body' => $body,
                'body_plain' => $this->sanitizer->toPlainText($body),
                'status' => $status,
                'approved_at' => $status === MemoryStatus::Approved ? now() : null,
                'submitted_via' => 'link',
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]);

            $this->mediaStore->attachFromRequest($request, $memory);

            return $memory;
        });

        ActivityLog::record('memory.submitted', $memorial, null, ['memory_id' => $memory->id, 'status' => $status->value]);
        $this->notifyOwner($memory);

        return redirect()
            ->route('memories.create', [$memorial, $token])
            ->with('memory_submitted', true)
            ->with('memory_approved', $status === MemoryStatus::Approved);
    }

    protected function notifyOwner(Memory $memory): void
    {
        $memorial = $memory->memorial;
        $owner = $memorial->owner;
        if (! $memorial->notify_owner || ! $owner?->email) {
            return;
        }
        if (! $this->settings->mailConfigured() && ! app()->environment(['local', 'testing'])) {
            return;
        }
        try {
            Mail::to($owner->email)->send(new NewMemoryMail($memory));
        } catch (Throwable) {
            // Notification failures must never break the submission.
        }
    }
}
