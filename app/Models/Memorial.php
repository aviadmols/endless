<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MemoryStatus;
use App\Enums\Religion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Memorial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'slug', 'first_name', 'last_name', 'gender', 'subtitle',
        'birth_date', 'death_date', 'dates_text', 'hebrew_dates',
        'religion', 'religion_icon_path', 'video_url',
        'portrait_image_path', 'portrait_video_path', 'hero_image_path', 'hero_video_path',
        'biography_title', 'biography', 'quote', 'quote_name', 'founder_name',
        'share_token', 'visibility', 'require_approval', 'notify_owner', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
            'gender' => Gender::class,
            'religion' => Religion::class,
            'require_approval' => 'boolean',
            'notify_owner' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Memorial $memorial) {
            $memorial->share_token ??= (string) Str::uuid();
            $memorial->published_at ??= now();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ------------------------------------------------------------ relations */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Reads better at the call site than ->user. */
    public function owner(): BelongsTo
    {
        return $this->user();
    }

    public function images(): HasMany
    {
        return $this->hasMany(MemorialImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class)->latest();
    }

    /** The owner's saved book configuration, if they have opened that tab. */
    public function book(): HasOne
    {
        return $this->hasOne(Book::class);
    }

    public function approvedMemories(): HasMany
    {
        return $this->memories()->where('status', MemoryStatus::Approved->value);
    }

    public function pendingMemories(): HasMany
    {
        return $this->memories()->where('status', MemoryStatus::Pending->value);
    }

    /* ------------------------------------------------------------ accessors */

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function getDisplaySubtitleAttribute(): string
    {
        return $this->subtitle ?: $this->gender->defaultSubtitle();
    }

    /** "15.8.2022 – 19.6.1951" (death first, like the reference) or the free-text override. */
    public function getDatesDisplayAttribute(): ?string
    {
        if ($this->dates_text) {
            return $this->dates_text;
        }
        $parts = [];
        if ($this->death_date) {
            $parts[] = $this->death_date->format('j.n.Y');
        }
        if ($this->birth_date) {
            $parts[] = $this->birth_date->format('j.n.Y');
        }

        return $parts ? implode(' – ', $parts) : null;
    }

    public function getFounderDisplayAttribute(): ?string
    {
        $name = $this->founder_name ?: $this->owner?->name;

        return $name ? 'הוקם ע״י '.$name : null;
    }

    public function getUrlAttribute(): string
    {
        return route('memorials.show', $this);
    }

    public function getShareUrlAttribute(): string
    {
        return route('memories.create', [$this, $this->share_token]);
    }

    public function getPortraitUrlAttribute(): ?string
    {
        return $this->portrait_image_path ? Storage::disk('public')->url($this->portrait_image_path) : null;
    }

    public function getPortraitVideoUrlAttribute(): ?string
    {
        if ($this->portrait_video_path) {
            return Storage::disk('public')->url($this->portrait_video_path);
        }

        return $this->video_url ?: null;
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->hero_image_path ? Storage::disk('public')->url($this->hero_image_path) : null;
    }

    public function getHeroVideoUrlAttribute(): ?string
    {
        return $this->hero_video_path ? Storage::disk('public')->url($this->hero_video_path) : null;
    }

    public function getReligionIconUrlAttribute(): ?string
    {
        return $this->religion_icon_path ? Storage::disk('public')->url($this->religion_icon_path) : null;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->portrait_image_path) {
            return $this->portrait_url;
        }
        $first = $this->images->first();

        return $first?->url;
    }

    /* ------------------------------------------------------------ scopes */

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /* ------------------------------------------------------------ helpers */

    public function regenerateShareToken(): void
    {
        $this->forceFill(['share_token' => (string) Str::uuid()])->save();
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $user->id === $this->user_id;
    }
}
