<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemorialRequest;
use App\Models\ActivityLog;
use App\Models\Memorial;
use App\Services\Html\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemorialController extends Controller
{
    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function edit(Request $request): View|RedirectResponse
    {
        $memorial = $this->memorialFor($request);
        if (! $memorial) {
            return redirect()->route('dashboard.index');
        }
        $memorial->load('images');

        return view('dashboard.memorial', [
            'memorial' => $memorial,
            'religions' => Religion::options(),
            'genders' => Gender::cases(),
        ]);
    }

    public function update(UpdateMemorialRequest $request): RedirectResponse
    {
        $memorial = $this->memorialFor($request);
        abort_unless($memorial, 404);
        $this->authorize('update', $memorial);

        $data = $request->validated();
        $data['biography'] = $this->sanitizer->clean($data['biography'] ?? '') ?: null;
        $data['require_approval'] = $request->boolean('require_approval');
        $data['notify_owner'] = $request->boolean('notify_owner');
        $data['subtitle'] = filled($data['subtitle'] ?? null) ? $data['subtitle'] : Gender::from($data['gender'])->defaultSubtitle();

        $memorial->fill($data)->save();
        ActivityLog::record('memorial.updated', $memorial);

        return redirect()->route('dashboard.memorial.edit', ['#'.$request->input('section', 'details')])
            ->with('status', 'העמוד עודכן בהצלחה.');
    }

    protected function memorialFor(Request $request): ?Memorial
    {
        return $request->user()->primaryMemorial();
    }
}
