<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\MemoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemoryRequest;
use App\Models\ActivityLog;
use App\Models\Memory;
use App\Models\MemoryImage;
use App\Services\Html\HtmlSanitizer;
use App\Services\Media\MemoryMediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemoryController extends Controller
{
    public function __construct(protected HtmlSanitizer $sanitizer, protected MemoryMediaStore $mediaStore) {}

    public function index(Request $request): View|RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial();
        if (! $memorial) {
            return redirect()->route('dashboard.index');
        }

        $status = $request->query('status', 'all');
        $counts = [
            'all' => $memorial->memories()->count(),
            'pending' => $memorial->memories()->pending()->count(),
            'approved' => $memorial->memories()->approved()->count(),
            'rejected' => $memorial->memories()->where('status', MemoryStatus::Rejected->value)->count(),
        ];

        $memories = $memorial->memories()->with('media')
            ->status($status === 'all' ? null : $status)
            ->paginate(12)->withQueryString();

        return view('dashboard.memories.index', compact('memorial', 'memories', 'counts', 'status'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial();
        if (! $memorial) {
            return redirect()->route('dashboard.index');
        }

        return view('dashboard.memories.form', ['memorial' => $memorial, 'memory' => null]);
    }

    public function store(StoreMemoryRequest $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $body = $this->sanitizer->clean($request->input('body'));
        $memory = $memorial->memories()->create([
            'user_id' => $request->user()->id,
            'author_name' => $request->string('author_name')->trim(),
            'title' => $request->input('title') ?: null,
            'body' => $body,
            'body_plain' => $this->sanitizer->toPlainText($body),
            'status' => MemoryStatus::Approved,
            'approved_at' => now(),
            'submitted_via' => 'owner',
            'ip' => $request->ip(),
        ]);
        $this->mediaStore->attachFromRequest($request, $memory);
        ActivityLog::record('memory.created_by_owner', $memorial, null, ['memory_id' => $memory->id]);

        return redirect()->route('dashboard.memories.index', ['status' => 'approved'])->with('status', 'הזיכרון נוסף לעמוד.');
    }

    public function edit(Request $request, Memory $memory): View
    {
        $this->authorize('update', $memory);
        $memory->load('media');

        return view('dashboard.memories.form', ['memorial' => $memory->memorial, 'memory' => $memory]);
    }

    public function update(StoreMemoryRequest $request, Memory $memory): RedirectResponse
    {
        $this->authorize('update', $memory);

        $body = $this->sanitizer->clean($request->input('body'));
        $memory->update([
            'author_name' => $request->string('author_name')->trim(),
            'title' => $request->input('title') ?: null,
            'body' => $body,
            'body_plain' => $this->sanitizer->toPlainText($body),
        ]);
        $this->mediaStore->attachFromRequest($request, $memory);
        ActivityLog::record('memory.updated', $memory->memorial, null, ['memory_id' => $memory->id]);

        return redirect()->route('dashboard.memories.index', ['status' => $memory->status->value])->with('status', 'הזיכרון עודכן.');
    }

    public function approve(Memory $memory): RedirectResponse
    {
        $this->authorize('moderate', $memory);
        $memory->approve();
        ActivityLog::record('memory.approved', $memory->memorial, null, ['memory_id' => $memory->id]);

        return back()->with('status', 'הזיכרון אושר ומוצג בעמוד.');
    }

    public function reject(Memory $memory): RedirectResponse
    {
        $this->authorize('moderate', $memory);
        $memory->reject();
        ActivityLog::record('memory.rejected', $memory->memorial, null, ['memory_id' => $memory->id]);

        return back()->with('status', 'הזיכרון הוסתר מהעמוד.');
    }

    public function destroy(Memory $memory): RedirectResponse
    {
        $this->authorize('delete', $memory);
        foreach ($memory->media as $media) {
            $media->delete();
        }
        $memory->delete();

        return back()->with('status', 'הזיכרון נמחק.');
    }

    public function destroyImage(Memory $memory, MemoryImage $image): RedirectResponse
    {
        $this->authorize('update', $memory);
        abort_unless($image->memory_id === $memory->id, 404);
        $image->delete();

        return back()->with('status', $image->is_video ? 'הסרטון הוסר.' : 'התמונה הוסרה.');
    }
}
