<?php

namespace App\Services\Book;

/** One printed page of the preview. */
class BookPage
{
    /**
     * @param  'cover'|'opening'|'divider'|'memory'|'photos'|'closing'  $type
     * @param  array<int,array{url:string,alt:string}>  $images
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $eyebrow = null,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?string $caption = null,
        public readonly array $images = [],
        public readonly int $columns = 2,
    ) {}
}
