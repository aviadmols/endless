<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\MemoryStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $memorial = $user->primaryMemorial();

        if (! $memorial) {
            return $user->is_admin
                ? redirect()->route('admin.index')
                : redirect()->route('register')->with('status', 'עדיין אין לכם עמוד הנצחה. צרו אחד עכשיו.');
        }

        $memorial->loadCount([
            'memories as approved_count' => fn ($q) => $q->where('status', MemoryStatus::Approved->value),
            'memories as pending_count' => fn ($q) => $q->where('status', MemoryStatus::Pending->value),
            'images as images_count',
        ]);

        $pending = $memorial->pendingMemories()->with('media')->limit(5)->get();
        $recent = $memorial->approvedMemories()->with('media')->limit(4)->get();

        $completeness = $this->completeness($memorial);

        return view('dashboard.overview', compact('memorial', 'pending', 'recent', 'completeness'));
    }

    /** Simple "how complete is your page" checklist for the overview card. */
    protected function completeness($memorial): array
    {
        $items = [
            ['label' => 'תמונת פרופיל', 'done' => (bool) $memorial->portrait_image_path || (bool) $memorial->portrait_video_path, 'route' => route('dashboard.memorial.edit').'#media'],
            ['label' => 'ביוגרפיה', 'done' => filled($memorial->biography), 'route' => route('dashboard.memorial.edit').'#bio'],
            ['label' => 'תאריכים', 'done' => (bool) $memorial->dates_display, 'route' => route('dashboard.memorial.edit').'#details'],
            ['label' => 'גלריית תמונות', 'done' => $memorial->images_count > 0, 'route' => route('dashboard.memorial.edit').'#gallery'],
            ['label' => 'ציטוט', 'done' => filled($memorial->quote), 'route' => route('dashboard.memorial.edit').'#quote'],
            ['label' => 'זיכרון ראשון', 'done' => $memorial->approved_count > 0, 'route' => route('dashboard.memories.create')],
        ];
        $done = count(array_filter($items, fn ($i) => $i['done']));

        return ['items' => $items, 'done' => $done, 'total' => count($items), 'percent' => (int) round($done / count($items) * 100)];
    }
}
