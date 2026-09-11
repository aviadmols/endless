<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Stores uploaded images on the public disk as optimised WebP (max 2000px) + a 700px thumbnail.
 * Videos are stored as-is.
 */
class ImageProcessor
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::gd();
    }

    /**
     * @return array{path:string, thumb_path:string, width:int, height:int}
     */
    public function store(UploadedFile $file, string $dir): array
    {
        $disk = Storage::disk('public');
        $dir = trim($dir, '/');
        $id = Str::uuid()->toString();
        $maxPx = (int) config('endless.uploads.image_max_px', 2000);
        $thumbPx = (int) config('endless.uploads.thumb_px', 700);
        $quality = (int) config('endless.uploads.webp_quality', 82);
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');

        $image = $this->manager->read($file->getRealPath());
        $image->scaleDown(width: $maxPx, height: $maxPx);
        $width = $image->width();
        $height = $image->height();

        if ($ext === 'gif') {
            // Keep animated GIFs untouched, only build a WebP thumbnail.
            $path = "{$dir}/{$id}.gif";
            $disk->putFileAs($dir, $file, "{$id}.gif");
        } else {
            $path = "{$dir}/{$id}.webp";
            $disk->put($path, (string) $image->toWebp(quality: $quality));
        }

        $thumbPath = "{$dir}/{$id}_thumb.webp";
        $thumb = $this->manager->read($file->getRealPath())->scaleDown(width: $thumbPx, height: $thumbPx);
        $disk->put($thumbPath, (string) $thumb->toWebp(quality: $quality));

        return ['path' => $path, 'thumb_path' => $thumbPath, 'width' => $width, 'height' => $height];
    }

    /** Store a small icon (SVG/PNG) without re-encoding. */
    public function storeRaw(UploadedFile $file, string $dir): string
    {
        $dir = trim($dir, '/');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $name = Str::uuid()->toString().'.'.$ext;
        Storage::disk('public')->putFileAs($dir, $file, $name);

        return "{$dir}/{$name}";
    }

    public function storeVideo(UploadedFile $file, string $dir): string
    {
        return $this->storeRaw($file, $dir);
    }

    /** @param  array<int, string|null>  $paths */
    public function delete(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (Throwable) {
                // ignore
            }
        }
    }
}
