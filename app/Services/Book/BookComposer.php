<?php

namespace App\Services\Book;

use App\Enums\BookContent;
use App\Enums\BookSize;
use App\Enums\MediaType;
use App\Models\Memorial;
use App\Models\Memory;
use Illuminate\Support\Str;

/**
 * Lays a memorial out as book pages.
 *
 * This is the preview's source of truth, not a print pipeline: text is split by
 * an approximate per-page character budget rather than by real typesetting, so
 * the page count is an estimate and is labelled as one in the UI.
 */
class BookComposer
{
    /** A memory whose text is shorter than this keeps its photos on the same page. */
    private const INLINE_TEXT_RATIO = 0.38;

    /** @return array<int,BookPage> */
    public function compose(Memorial $memorial, BookContent $content, BookSize $size, array $overrides = []): array
    {
        $pages = [$this->cover($memorial)];

        foreach ($this->opening($memorial, $size) as $page) {
            $pages[] = $page;
        }

        if ($content->includesMemories()) {
            $memories = $memorial->approvedMemories()->with('media')->get()->reverse()->values();

            if ($memories->isNotEmpty()) {
                $pages[] = new BookPage(type: 'divider', key: 'divider:memories', eyebrow: 'פרק ראשון', title: 'הזיכרונות');

                foreach ($memories as $memory) {
                    foreach ($this->memoryPages($memory, $size) as $page) {
                        $pages[] = $page;
                    }
                }
            }
        }

        if ($content->includesPhotos()) {
            $images = $memorial->images()->get();

            if ($images->isNotEmpty()) {
                $pages[] = new BookPage(
                    type: 'divider',
                    key: 'divider:photos',
                    eyebrow: $content === BookContent::Both ? 'פרק שני' : 'הגלריה',
                    title: 'תמונות',
                );

                foreach ($images->chunk($size->photosPerPage()) as $n => $chunk) {
                    $pages[] = new BookPage(
                        type: 'photos',
                        key: "photos:gallery:{$n}",
                        images: $chunk->map(fn ($image) => ['url' => $image->url, 'alt' => (string) $image->alt])->values()->all(),
                        columns: $size->photoColumns(),
                    );
                }
            }
        }

        $pages[] = $this->closing($memorial);

        // Books are printed on folded sheets, so the page count is always a multiple of four.
        $blanks = (4 - (count($pages) % 4)) % 4;
        for ($i = 0; $i < $blanks; $i++) {
            $pages[] = new BookPage(type: 'blank');
        }

        return array_map(
            fn (BookPage $page) => isset($overrides[$page->key]) ? $page->withOverrides($overrides[$page->key]) : $page,
            $pages,
        );
    }

    private function cover(Memorial $memorial): BookPage
    {
        $cover = $memorial->cover_image_url;

        return new BookPage(
            type: 'cover',
            key: 'cover',
            eyebrow: $memorial->display_subtitle,
            title: $memorial->full_name,
            caption: $memorial->dates_display,
            images: $cover ? [['url' => $cover, 'alt' => $memorial->full_name]] : [],
        );
    }

    /** @return array<int,BookPage> */
    private function opening(Memorial $memorial, BookSize $size): array
    {
        $biography = $this->plain($memorial->biography);

        if ($biography === '') {
            return [new BookPage(
                type: 'opening',
                key: 'opening:0',
                title: $memorial->full_name,
                caption: $memorial->dates_display,
            )];
        }

        $pages = [];
        foreach ($this->split($biography, $size, titled: true) as $i => $chunk) {
            $pages[] = new BookPage(
                type: 'opening',
                key: "opening:{$i}",
                title: $i === 0 ? ($memorial->biography_title ?: $memorial->full_name) : null,
                body: $chunk,
                caption: $i === 0 ? $memorial->dates_display : null,
            );
        }

        return $pages;
    }

    /** @return array<int,BookPage> */
    private function memoryPages(Memory $memory, BookSize $size): array
    {
        $images = $memory->media
            ->where('type', MediaType::Image)
            ->map(fn ($image) => ['url' => $image->url, 'alt' => ''])
            ->values()
            ->all();

        $text = $this->plain($memory->body_plain ?: $memory->body);
        $byline = trim($memory->author_name.' · '.$memory->date_display, ' ·');

        // Short enough to sit under its photos; otherwise the photos get their own page.
        $inlineLines = (int) floor($size->linesPerPage() * self::INLINE_TEXT_RATIO);
        $inline = $images !== [] && $this->linesFor($text, $size->charsPerLine()) <= $inlineLines;

        $pages = [];

        if ($images !== [] && ! $inline) {
            foreach (array_chunk($images, $size->photosPerPage()) as $n => $chunk) {
                $pages[] = new BookPage(
                    type: 'photos',
                    key: "photos:memory:{$memory->id}:{$n}",
                    caption: $byline,
                    images: $chunk,
                    columns: count($chunk) === 1 ? 1 : $size->photoColumns(),
                );
            }
        }

        $titled = filled($memory->title);
        $chunks = $text === ''
            ? ['']
            : $this->split($text, $size, titled: $titled, lines: $inline ? $inlineLines : null);

        foreach ($chunks as $i => $chunk) {
            $pages[] = new BookPage(
                type: 'memory',
                key: "memory:{$memory->id}:{$i}",
                title: $i === 0 ? ($memory->title ?: null) : null,
                body: $chunk,
                caption: $i === count($chunks) - 1 ? $byline : null,
                images: ($inline && $i === 0) ? array_slice($images, 0, $size->photosPerPage()) : [],
                columns: count($images) === 1 ? 1 : $size->photoColumns(),
            );
        }

        return $pages;
    }

    private function closing(Memorial $memorial): BookPage
    {
        return new BookPage(
            type: 'closing',
            key: 'closing',
            title: $memorial->quote ?: null,
            caption: $memorial->quote_name ?: $memorial->founder_display,
        );
    }

    /** Rich text from the editor down to something we can measure and break. */
    private function plain(?string $html): string
    {
        $text = preg_replace('/<\/(p|div|h[1-6]|li)>/i', "\n\n", (string) $html);
        $text = preg_replace('/<br\s*\/?>/i', "\n", (string) $text);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", (string) $text);

        return trim((string) $text);
    }

    /**
     * Break text into page-sized chunks.
     *
     * The body keeps the line breaks the author typed, so the budget is counted
     * in rendered lines: a one-word line costs as much as a full one, and a long
     * line costs as many as it wraps into.
     *
     * @param  bool  $titled  the first page also carries a heading
     * @param  int|null  $lines  override the page's line budget (photos above the text)
     * @return array<int,string>
     */
    private function split(string $text, BookSize $size, bool $titled = false, ?int $lines = null): array
    {
        $perLine = $size->charsPerLine();
        $budget = max(3, $lines ?? $size->linesPerPage());
        $first = max(3, $titled ? $budget - 2 : $budget);

        $pages = [];
        $current = [];
        $used = 0;

        $flush = function () use (&$pages, &$current, &$used) {
            $page = trim(implode("\n", $current));
            if ($page !== '') {
                $pages[] = $page;
            }
            $current = [];
            $used = 0;
        };

        foreach (preg_split("/\n/u", $text) as $sourceLine) {
            foreach ($this->wrapToPage($sourceLine, $perLine, $budget) as $piece) {
                $cost = $this->linesFor($piece, $perLine);
                $allowed = $pages === [] ? $first : $budget;

                if ($used + $cost > $allowed && $current !== []) {
                    $flush();
                }

                // A page should not open on a blank line left over from the break.
                if ($current === [] && trim($piece) === '') {
                    continue;
                }

                $current[] = $piece;
                $used += $cost;
            }
        }

        $flush();

        return $pages ?: [''];
    }

    /** How many rendered lines a single source line occupies. */
    private function linesFor(string $text, int $perLine): int
    {
        $total = 0;
        foreach (preg_split("/\n/u", $text) as $line) {
            $total += max(1, (int) ceil(mb_strlen(trim($line)) / max(1, $perLine)));
        }

        return max(1, $total);
    }

    /**
     * Cut a source line that is longer than a whole page into page-sized pieces.
     *
     * @return array<int,string>
     */
    private function wrapToPage(string $line, int $perLine, int $budget): array
    {
        $max = $perLine * $budget;

        if (mb_strlen($line) <= $max) {
            return [$line];
        }

        $pieces = [];
        $buffer = '';

        foreach (preg_split('/\s+/u', trim($line)) as $word) {
            $candidate = $buffer === '' ? $word : $buffer.' '.$word;

            if (mb_strlen($candidate) <= $max) {
                $buffer = $candidate;
                continue;
            }

            $pieces[] = $buffer !== '' ? $buffer : Str::limit($word, $max, '');
            $buffer = $buffer !== '' ? $word : '';
        }

        if ($buffer !== '') {
            $pieces[] = $buffer;
        }

        return $pieces;
    }
}
