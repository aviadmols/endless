<?php

namespace App\Models;

use App\Enums\BookContent;
use App\Enums\BookSize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The owner's saved book configuration. There is at most one per memorial, and
 * it is only a draft — ordering opens when the payment page is wired up.
 */
class Book extends Model
{
    use HasFactory;

    public const MAX_COPIES = 500;

    protected $fillable = ['memorial_id', 'content', 'size', 'copies', 'page_count'];

    protected $attributes = [
        'content' => 'both',
        'size' => 'portrait_21_28',
        'copies' => 1,
        'page_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'content' => BookContent::class,
            'size' => BookSize::class,
            'copies' => 'integer',
            'page_count' => 'integer',
        ];
    }

    public function memorial(): BelongsTo
    {
        return $this->belongsTo(Memorial::class);
    }
}
