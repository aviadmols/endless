<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A photo or a video attached to a memory. The table is still called
 * `memory_images` for historical reasons; treat rows as "media".
 */
class MemoryImage extends Model
{
    use HasFactory;

    protected $fillable = ['memory_id', 'type', 'path', 'thumb_path', 'poster_path', 'mime', 'width', 'height', 'sort_order'];

    protected function casts(): array
    {
        return ['type' => MediaType::class];
    }

    public function memory(): BelongsTo
    {
        return $this->belongsTo(Memory::class);
    }

    /* ------------------------------------------------------------- scopes */

    public function scopeImages(Builder $q): Builder
    {
        return $q->where('type', MediaType::Image->value);
    }

    public function scopeVideos(Builder $q): Builder
    {
        return $q->where('type', MediaType::Video->value);
    }

    /* ---------------------------------------------------------- accessors */

    public function getIsVideoAttribute(): bool
    {
        return $this->type === MediaType::Video;
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->thumb_path ?: $this->poster_path ?: $this->path);
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path ? Storage::disk('public')->url($this->poster_path) : null;
    }

    public function getRatioAttribute(): float
    {
        if ($this->width && $this->height) {
            return $this->width / $this->height;
        }

        // Videos rarely report their size on upload; 4:3 keeps the masonry tidy.
        return $this->is_video ? 4 / 3 : 1.0;
    }

    protected static function booted(): void
    {
        static::deleted(function (MemoryImage $media) {
            foreach (array_filter([$media->path, $media->thumb_path, $media->poster_path]) as $p) {
                Storage::disk('public')->delete($p);
            }
        });
    }
}
