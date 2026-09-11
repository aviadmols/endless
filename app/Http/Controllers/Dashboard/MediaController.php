<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Single-file media slots of a memorial: portrait image/video, hero background image/video, custom religion icon. */
class MediaController extends Controller
{
    protected array $types = [
        'portrait_image' => ['column' => 'portrait_image_path', 'kind' => 'image', 'dir' => 'portrait'],
        'portrait_video' => ['column' => 'portrait_video_path', 'kind' => 'video', 'dir' => 'portrait'],
        'hero_image' => ['column' => 'hero_image_path', 'kind' => 'image', 'dir' => 'hero'],
        'hero_video' => ['column' => 'hero_video_path', 'kind' => 'video', 'dir' => 'hero'],
        'religion_icon' => ['column' => 'religion_icon_path', 'kind' => 'icon', 'dir' => 'icon'],
    ];

    public function __construct(protected ImageProcessor $images) {}

    public function store(Request $request, string $type): RedirectResponse
    {
        $meta = $this->types[$type] ?? abort(404);
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $rules = match ($meta['kind']) {
            'image' => ['required', 'image', 'mimes:'.implode(',', config('endless.uploads.image_mimes')), 'max:'.config('endless.uploads.image_max_kb')],
            'video' => ['required', 'file', 'mimes:'.implode(',', config('endless.uploads.video_mimes')), 'max:'.config('endless.uploads.video_max_kb')],
            'icon' => ['required', 'file', 'mimes:svg,png,webp,jpg,jpeg', 'max:512'],
        };
        $request->validate(['file' => $rules], [], ['file' => 'הקובץ']);

        $file = $request->file('file');
        $dir = "memorials/{$memorial->id}/{$meta['dir']}";

        $path = match ($meta['kind']) {
            'image' => $this->images->store($file, $dir)['path'],
            'video' => $this->images->storeVideo($file, $dir),
            'icon' => $this->images->storeRaw($file, $dir),
        };

        $this->images->delete([$memorial->{$meta['column']}]);
        $memorial->forceFill([$meta['column'] => $path])->save();
        ActivityLog::record('memorial.media.updated', $memorial, null, ['type' => $type]);

        return back()->with('status', 'הקובץ הועלה בהצלחה.')->withFragment('media');
    }

    public function destroy(Request $request, string $type): RedirectResponse
    {
        $meta = $this->types[$type] ?? abort(404);
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $this->images->delete([$memorial->{$meta['column']}]);
        $memorial->forceFill([$meta['column'] => null])->save();

        return back()->with('status', 'הקובץ הוסר.')->withFragment('media');
    }
}
