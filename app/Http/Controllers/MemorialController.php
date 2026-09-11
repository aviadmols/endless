<?php

namespace App\Http\Controllers;

use App\Models\Memorial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemorialController extends Controller
{
    public function show(Request $request, Memorial $memorial): View
    {
        $this->countView($request, $memorial);

        $memorial->load(['images', 'owner']);
        $perPage = (int) config('endless.feed.per_page', 8);

        $memories = $memorial->approvedMemories()->with('media')->paginate($perPage);

        return view('memorials.show', [
            'memorial' => $memorial,
            'memories' => $memories,
            'isOwner' => $memorial->isOwnedBy($request->user()) || (bool) $request->user()?->is_admin,
            'totalMemories' => $memories->total(),
        ]);
    }

    /** Next page of approved memories for the feed (JSON with rendered HTML). */
    public function feed(Request $request, Memorial $memorial): JsonResponse
    {
        $perPage = (int) config('endless.feed.per_page', 8);
        $memories = $memorial->approvedMemories()->with('media')->paginate($perPage);

        $html = '';
        foreach ($memories as $memory) {
            $html .= view('memories._feed_item', ['memory' => $memory, 'memorial' => $memorial])->render();
        }

        return response()->json([
            'html' => $html,
            'hasMore' => $memories->hasMorePages(),
            'nextPage' => $memories->currentPage() + 1,
            'total' => $memories->total(),
        ]);
    }

    protected function countView(Request $request, Memorial $memorial): void
    {
        $key = 'viewed.memorial.'.$memorial->id;
        if ($request->session()->has($key) || $memorial->isOwnedBy($request->user())) {
            return;
        }
        $memorial->increment('views_count');
        $request->session()->put($key, true);
    }
}
