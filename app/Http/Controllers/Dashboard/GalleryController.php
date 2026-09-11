<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MemorialImage;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(protected ImageProcessor $images) {}

    public function store(Request $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $request->validate([
            'images' => ['required', 'array', 'max:30'],
            'images.*' => ['image', 'mimes:'.implode(',', config('endless.uploads.image_mimes')), 'max:'.config('endless.uploads.image_max_kb')],
        ], [], ['images' => 'תמונות', 'images.*' => 'תמונה']);

        $max = (int) config('endless.uploads.gallery_max_images', 60);
        $existing = $memorial->images()->count();
        $order = (int) ($memorial->images()->max('sort_order') ?? -1);
        $added = 0;

        foreach ((array) $request->file('images') as $file) {
            if ($existing + $added >= $max) {
                break;
            }
            $memorial->images()->create($this->images->store($file, "memorials/{$memorial->id}/gallery") + ['sort_order' => ++$order]);
            $added++;
        }

        ActivityLog::record('gallery.added', $memorial, null, ['count' => $added]);

        return back()->with('status', $added.' תמונות נוספו לגלריה.')->withFragment('gallery');
    }

    public function reorder(Request $request): JsonResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $ids = array_map('intval', (array) $request->input('order', []));
        foreach ($ids as $i => $id) {
            $memorial->images()->where('id', $id)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, MemorialImage $image): RedirectResponse
    {
        $this->authorize('update', $image->memorial);
        $request->validate(['alt' => ['nullable', 'string', 'max:160']]);
        $image->update(['alt' => $request->input('alt')]);

        return back()->with('status', 'הכיתוב נשמר.')->withFragment('gallery');
    }

    public function destroy(Request $request, MemorialImage $image): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $image->memorial);
        $image->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'התמונה נמחקה.')->withFragment('gallery');
    }
}
