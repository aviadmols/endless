<?php

namespace App\Services\Book;

/** One printed page of the preview. */
class BookPage
{
    /**
     * @param  'cover'|'opening'|'divider'|'memory'|'photos'|'closing'|'blank'  $type
     * @param  string  $key  identity across recomposition, so an edit sticks to its page
     * @param  array<int,array{url:string,alt:string}>  $images
     */
    public function __construct(
        public readonly string $type,
        public readonly string $key = '',
        public readonly ?string $eyebrow = null,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?string $caption = null,
        public readonly array $images = [],
        public readonly int $columns = 2,
    ) {}

    /** Which of the text fields this kind of page actually shows. */
    public function editableFields(): array
    {
        return match ($this->type) {
            'cover' => ['eyebrow', 'title', 'caption'],
            'opening', 'memory' => ['title', 'body', 'caption'],
            'divider' => ['eyebrow', 'title'],
            'photos' => ['caption'],
            'closing' => ['title', 'caption'],
            default => [],
        };
    }

    public function isEditable(): bool
    {
        return $this->key !== '' && $this->editableFields() !== [];
    }

    /** A copy with the owner's edits applied. */
    public function withOverrides(array $overrides): self
    {
        $pick = fn (string $field, ?string $fallback) => array_key_exists($field, $overrides)
            ? (trim((string) $overrides[$field]) ?: null)
            : $fallback;

        return new self(
            type: $this->type,
            key: $this->key,
            eyebrow: $pick('eyebrow', $this->eyebrow),
            title: $pick('title', $this->title),
            body: $pick('body', $this->body),
            caption: $pick('caption', $this->caption),
            images: $this->images,
            columns: $this->columns,
        );
    }
}
