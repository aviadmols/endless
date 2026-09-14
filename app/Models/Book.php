<?php

namespace App\Models;

use App\Enums\BookContent;
use App\Enums\BookCover;
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

    /** Text fields of a page the owner may rewrite. */
    public const EDITABLE_FIELDS = ['eyebrow', 'title', 'body', 'caption'];

    protected $fillable = ['memorial_id', 'content', 'size', 'cover', 'overrides', 'excluded_images', 'copies', 'page_count'];

    protected $attributes = [
        'content' => 'both',
        'size' => 'portrait_21_28',
        'cover' => 'white',
        'copies' => 1,
        'page_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'content' => BookContent::class,
            'size' => BookSize::class,
            'cover' => BookCover::class,
            'overrides' => 'array',
            'excluded_images' => 'array',
            'copies' => 'integer',
            'page_count' => 'integer',
        ];
    }

    /** Photo keys ("gallery:12") the owner has taken out of the book. */
    public function excluded(): array
    {
        return array_values($this->excluded_images ?? []);
    }

    /** @param  array<int,string>  $remove  keys to take out; anything else offered is put back */
    public function setPhotoExclusions(array $offered, array $remove): void
    {
        $kept = array_diff($this->excluded(), $offered);

        $this->excluded_images = array_values(array_unique([...$kept, ...array_intersect($offered, $remove)]));
        $this->save();
    }

    /**
     * Store one page's edits. A field set back to the composed text is dropped
     * rather than frozen, so the page keeps following the memorial.
     */
    public function overridePage(string $key, array $fields, array $composed): void
    {
        $overrides = $this->overrides ?? [];
        $edits = [];

        foreach ($fields as $field => $value) {
            $value = trim((string) $value);
            if ($value !== trim((string) ($composed[$field] ?? ''))) {
                $edits[$field] = $value;
            }
        }

        if ($edits === []) {
            unset($overrides[$key]);
        } else {
            $overrides[$key] = $edits;
        }

        $this->overrides = $overrides;
        $this->save();
    }

    public function memorial(): BelongsTo
    {
        return $this->belongsTo(Memorial::class);
    }
}
