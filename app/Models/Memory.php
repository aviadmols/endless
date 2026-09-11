<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Enums\MemoryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Memory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'memorial_id', 'user_id', 'author_name', 'author_email', 'author_phone', 'title',
        'body', 'body_plain', 'status', 'approved_at', 'submitted_via', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => MemoryStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function memorial(): BelongsTo
    {
        return $this->belongsTo(Memorial::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Photos and videos together, in display order. */
    public function media(): HasMany
    {
        return $this->hasMany(MemoryImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->media()->images();
    }

    public function videos(): HasMany
    {
        return $this->media()->videos();
    }

    /* ------------------------------------------------------------ scopes */

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', MemoryStatus::Approved->value);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', MemoryStatus::Pending->value);
    }

    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    /* ------------------------------------------------------------ accessors */

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === MemoryStatus::Approved;
    }

    /** The card image for the feed: the first photo, or the first video as a fallback. */
    public function getCoverAttribute(): ?MemoryImage
    {
        $media = $this->relationLoaded('media') ? $this->media : $this->media()->get();

        return $media->firstWhere('type', MediaType::Image) ?? $media->first();
    }

    public function getHasMediaAttribute(): bool
    {
        return ($this->relationLoaded('media') ? $this->media : $this->media()->get())->isNotEmpty();
    }

    public function excerpt(int $words = 16): string
    {
        $plain = trim((string) $this->body_plain);

        return Str::words($plain, $words, '…');
    }

    /** "12 באוקטובר 2024" */
    public function getDateDisplayAttribute(): string
    {
        return $this->created_at->locale('he')->translatedFormat('j בF Y');
    }

    public function getUrlAttribute(): string
    {
        return route('memories.show', [$this->memorial, $this]);
    }

    /* ------------------------------------------------------------ actions */

    public function approve(): void
    {
        $this->forceFill(['status' => MemoryStatus::Approved, 'approved_at' => now()])->save();
    }

    public function reject(): void
    {
        $this->forceFill(['status' => MemoryStatus::Rejected])->save();
    }
}
