<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MemorialImage extends Model
{
    use HasFactory;

    protected $fillable = ['memorial_id', 'path', 'thumb_path', 'width', 'height', 'alt', 'sort_order'];

    public function memorial(): BelongsTo
    {
        return $this->belongsTo(Memorial::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->thumb_path ?: $this->path);
    }

    public function getRatioAttribute(): float
    {
        return ($this->width && $this->height) ? $this->width / $this->height : 1.0;
    }

    protected static function booted(): void
    {
        static::deleted(function (MemorialImage $image) {
            foreach (array_filter([$image->path, $image->thumb_path]) as $p) {
                Storage::disk('public')->delete($p);
            }
        });
    }
}
