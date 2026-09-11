<?php

namespace App\Console\Commands;

use App\Enums\Gender;
use App\Enums\MediaType;
use App\Enums\MemoryStatus;
use App\Enums\Religion;
use App\Models\Memorial;
use App\Models\User;
use App\Services\Html\HtmlSanitizer;
use App\Services\Media\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports a memorial from a JSON manifest (see database/import/*.json).
 *
 * The manifest holds the text; the photos and videos are pulled from their
 * source URLs at run time, so the repository stays small and the media lands
 * straight on the server's storage volume.
 */
class ImportMemorial extends Command
{
    protected $signature = 'endless:import
                            {manifest=kochav : Manifest name under database/import (without .json)}
                            {--fresh : Delete the existing memorial with this slug first}
                            {--skip-media : Import the text only}';

    protected $description = 'Import a memorial, its gallery and its memories from a manifest';

    public function __construct(
        protected HtmlSanitizer $sanitizer,
        protected ImageProcessor $images,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = database_path('import/'.$this->argument('manifest').'.json');
        if (! is_file($path)) {
            $this->error("Manifest not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $slug = $data['memorial']['slug'];

        if ($this->option('fresh')) {
            Memorial::withTrashed()->where('slug', $slug)->each(function (Memorial $old) {
                $this->line("  removing existing memorial #{$old->id}");
                Storage::disk('public')->deleteDirectory("memorials/{$old->id}");
                $old->forceDelete();
            });
        }

        $owner = $this->owner($data['owner']);
        $memorial = $this->memorial($data['memorial'], $owner);

        if (! $this->option('skip-media')) {
            $this->media($memorial, $data['memorial']);
            $this->gallery($memorial, $data['gallery'] ?? []);
            $this->portraitFallback($memorial);
        }

        $this->memories($memorial, $data['memories'] ?? []);

        $this->newLine();
        $this->info("Imported {$memorial->full_name} → ".route('memorials.show', $memorial));
        $this->line('  owner: '.$owner->email.' / '.$owner->phone);
        $this->line('  gallery: '.$memorial->images()->count().' · memories: '.$memorial->memories()->count());

        return self::SUCCESS;
    }

    /* ------------------------------------------------------------------ */

    protected function owner(array $data): User
    {
        $user = User::where('phone', $data['phone'])->orWhere('email', $data['email'])->first();

        $attributes = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ];

        if ($user) {
            $user->fill($attributes)->save();
        } else {
            $user = User::create($attributes);
        }

        $this->line('owner ready: '.$user->name.' ('.$user->phone.')');

        return $user;
    }

    protected function memorial(array $data, User $owner): Memorial
    {
        $memorial = Memorial::where('slug', $data['slug'])->first() ?? new Memorial(['slug' => $data['slug']]);

        $memorial->fill([
            'user_id' => $owner->id,
            'slug' => $data['slug'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'gender' => Gender::from($data['gender'] ?? 'male'),
            'subtitle' => $data['subtitle'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'dates_text' => $data['dates_text'] ?? null,
            'religion' => Religion::from($data['religion'] ?? 'jewish'),
            'biography_title' => $data['biography_title'] ?? null,
            'biography' => $this->sanitizer->clean($data['biography_html'] ?? '') ?: null,
            'quote' => $data['quote'] ?? null,
            'quote_name' => $data['quote_name'] ?? null,
            'founder_name' => $data['founder_name'] ?? null,
            'visibility' => $data['visibility'] ?? 'private',
            'require_approval' => (bool) ($data['require_approval'] ?? true),
        ])->save();

        $this->line('memorial ready: '.$memorial->full_name.' (/m/'.$memorial->slug.')');

        return $memorial;
    }

    protected function media(Memorial $memorial, array $data): void
    {
        foreach ([
            'hero_video_url' => 'hero_video_path',
            'portrait_video_url' => 'portrait_video_path',
        ] as $key => $column) {
            if (empty($data[$key]) || $memorial->{$column}) {
                continue;
            }
            $file = $this->download($data[$key], 'mp4');
            if (! $file) {
                continue;
            }
            $dir = "memorials/{$memorial->id}/".($column === 'hero_video_path' ? 'hero' : 'portrait');
            $memorial->forceFill([$column => $this->images->storeVideo($file, $dir)])->save();
            $this->line("  {$column} ✓");
        }

    }

    /** Gives the page a still portrait for sharing even when the hero shows a video. */
    protected function portraitFallback(Memorial $memorial): void
    {
        if ($memorial->portrait_image_path) {
            return;
        }
        if ($first = $memorial->images()->first()) {
            $memorial->forceFill(['portrait_image_path' => $first->path])->save();
            $this->line('  portrait fallback ✓');
        }
    }

    protected function gallery(Memorial $memorial, array $items): void
    {
        if ($memorial->images()->count() >= count($items) && count($items) > 0) {
            $this->line('gallery already imported, skipping');

            return;
        }

        $order = 0;
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $item) {
            $file = $this->download($item['url'], 'jpg');
            if ($file) {
                $memorial->images()->create(
                    $this->images->store($file, "memorials/{$memorial->id}/gallery")
                    + ['alt' => $item['alt'] ?? null, 'sort_order' => $order++]
                );
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function memories(Memorial $memorial, array $items): void
    {
        foreach ($items as $index => $item) {
            $body = $this->sanitizer->clean($item['body_html'] ?? '');
            if ($body === '') {
                continue;
            }

            $memory = $memorial->memories()->firstOrNew(['author_name' => $item['author_name']]);
            $isNew = ! $memory->exists;

            $memory->fill([
                'body' => $body,
                'body_plain' => $this->sanitizer->toPlainText($body),
                'status' => MemoryStatus::Approved,
                'approved_at' => $memory->approved_at ?? now()->subDays(count($items) - $index),
                'submitted_via' => 'link',
            ]);
            if ($isNew) {
                $memory->created_at = now()->subDays(count($items) - $index);
            }
            $memory->save();

            if (! $this->option('skip-media') && ! empty($item['image_url']) && $memory->media()->count() === 0) {
                $file = $this->download($item['image_url'], 'jpg');
                if ($file) {
                    $memory->media()->create(
                        $this->images->store($file, "memorials/{$memorial->id}/memories")
                        + ['type' => MediaType::Image, 'sort_order' => 0]
                    );
                }
            }

            $this->line('  memory: '.$item['author_name'].($isNew ? ' (new)' : ' (updated)'));
        }
    }

    /** Fetches a remote file into a temporary UploadedFile, or null when it cannot be read. */
    protected function download(string $url, string $fallbackExtension): ?UploadedFile
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'EndlessImporter/1.0'])
                ->timeout(120)
                ->retry(2, 1000)
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 128) {
                $this->warn("  download failed ({$response->status()}): ".Str::limit($url, 80));

                return null;
            }

            $extension = $this->extensionFor($response->header('Content-Type'), $url) ?: $fallbackExtension;
            $tmp = tempnam(sys_get_temp_dir(), 'endless').'.'.$extension;
            file_put_contents($tmp, $response->body());

            return new UploadedFile($tmp, basename(parse_url($url, PHP_URL_PATH) ?: 'file').'.'.$extension, $response->header('Content-Type'), null, true);
        } catch (Throwable $e) {
            $this->warn('  download error: '.Str::limit($e->getMessage(), 100));

            return null;
        }
    }

    protected function extensionFor(?string $contentType, string $url): ?string
    {
        $map = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'image/avif' => 'avif',
            'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov',
        ];

        $type = strtolower(trim(explode(';', (string) $contentType)[0]));
        if (isset($map[$type])) {
            return $map[$type];
        }

        $fromUrl = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

        return in_array($fromUrl, array_values($map), true) ? $fromUrl : null;
    }
}
