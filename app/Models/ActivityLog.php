<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'memorial_id', 'action', 'meta', 'ip'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memorial(): BelongsTo
    {
        return $this->belongsTo(Memorial::class);
    }

    public static function record(string $action, ?Memorial $memorial = null, ?User $user = null, array $meta = []): self
    {
        return static::create([
            'action' => $action,
            'memorial_id' => $memorial?->id,
            'user_id' => $user?->id ?? auth()->id(),
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
