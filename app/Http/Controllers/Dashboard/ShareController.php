<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShareController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial();
        if (! $memorial) {
            return redirect()->route('dashboard.index');
        }

        $name = $memorial->full_name;
        $shareText = "הזמנה להעלות זיכרון לעמוד ההנצחה של {$name}:\n{$memorial->share_url}";
        $pageText = "עמוד ההנצחה של {$name}:\n{$memorial->url}";

        return view('dashboard.share', [
            'memorial' => $memorial,
            'whatsappShare' => 'https://wa.me/?text='.rawurlencode($shareText),
            'whatsappPage' => 'https://wa.me/?text='.rawurlencode($pageText),
            'mailShare' => 'mailto:?subject='.rawurlencode("זיכרון ל{$name}").'&body='.rawurlencode($shareText),
        ]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $memorial->regenerateShareToken();
        ActivityLog::record('share.regenerated', $memorial);

        return back()->with('status', 'נוצר קישור שיתוף חדש. הקישור הקודם אינו פעיל יותר.');
    }
}
