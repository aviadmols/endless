<?php

namespace App\Services\Media;

use App\Enums\MediaType;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/** Saves the photos and videos attached to a memory. */
class MemoryMediaStore
{
    public function __construct(protected ImageProcessor $images) {}

    /**
     * Stores every uploaded file under the given request key and returns how many were kept.
     */
    public function attachFromRequest(Request $request, Memory $memory, string $key = 'media'): int
    {
        $files = array_filter((array) $request->file($key, []));
        if (! $files) {
            return 0;
        }

        $order = (int) ($memory->media()->max('sort_order') ?? -1);
        $limit = (int) config('endless.uploads.memory_max_images', 10);
        $existing = $memory->media()->count();
        $saved = 0;

        foreach ($files as $file) {
            if ($existing + $saved >= $limit) {
                break;
            }
            $this->attach($memory, $file, ++$order);
            $saved++;
        }

        return $saved;
    }

    public function attach(Memory $memory, UploadedFile $file, int $sortOrder): void
    {
        $dir = "memorials/{$memory->memorial_id}/memories";

        if ($this->isVideo($file)) {
            $memory->media()->create([
                'type' => MediaType::Video,
                'path' => $this->images->storeVideo($file, $dir),
                'mime' => $file->getClientMimeType(),
                'sort_order' => $sortOrder,
            ]);

            return;
        }

        $memory->media()->create($this->images->store($file, $dir) + [
            'type' => MediaType::Image,
            'mime' => $file->getClientMimeType(),
            'sort_order' => $sortOrder,
        ]);
    }

    public function isVideo(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());

        return in_array($extension, array_map('strtolower', config('endless.uploads.video_mimes', [])), true)
            || str_starts_with((string) $file->getClientMimeType(), 'video/');
    }
}
