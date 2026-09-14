<?php

namespace Tests\Feature;

use App\Enums\BookContent;
use App\Enums\BookSize;
use App\Models\Book;
use App\Models\Memorial;
use App\Models\MemorialImage;
use App\Models\Memory;
use App\Models\User;
use App\Services\Book\BookComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Memorial $memorial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->memorial = Memorial::factory()->for($this->owner)->create([
            'slug' => 'demo',
            'first_name' => 'אברהם',
            'last_name' => 'כוכב',
        ]);
    }

    private function addPhotos(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            MemorialImage::create([
                'memorial_id' => $this->memorial->id,
                'path' => "gallery/{$i}.jpg",
                'sort_order' => $i,
            ]);
        }
    }

    public function test_the_builder_renders_with_a_preview(): void
    {
        Memory::factory()->for($this->memorial)->count(2)->create();

        $this->actingAs($this->owner)
            ->get(route('dashboard.book'))
            ->assertOk()
            ->assertSee('ספר הזיכרונות')
            ->assertSee('הדגמה')
            ->assertSee('אברהם כוכב')
            ->assertSee('data-book-page', escape: false);
    }

    public function test_an_empty_memorial_is_told_there_is_nothing_to_bind_yet(): void
    {
        $this->actingAs($this->owner)
            ->get(route('dashboard.book'))
            ->assertOk()
            ->assertSee('עדיין אין ממה להרכיב ספר')
            ->assertDontSee('data-book-page', escape: false);
    }

    public function test_the_owner_saves_a_configuration(): void
    {
        Memory::factory()->for($this->memorial)->count(2)->create();

        $this->actingAs($this->owner)
            ->put(route('dashboard.book.update'), [
                'content' => BookContent::Memories->value,
                'size' => BookSize::Square->value,
                'copies' => 12,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $book = Book::firstWhere('memorial_id', $this->memorial->id);

        $this->assertSame(BookContent::Memories, $book->content);
        $this->assertSame(BookSize::Square, $book->size);
        $this->assertSame(12, $book->copies);
        $this->assertGreaterThan(0, $book->page_count);
    }

    public function test_saving_twice_updates_the_same_book(): void
    {
        Memory::factory()->for($this->memorial)->create();

        $payload = ['content' => BookContent::Both->value, 'size' => BookSize::Portrait->value, 'copies' => 3];
        $this->actingAs($this->owner)->put(route('dashboard.book.update'), $payload);
        $this->actingAs($this->owner)->put(route('dashboard.book.update'), [...$payload, 'copies' => 7]);

        $this->assertSame(1, Book::count());
        $this->assertSame(7, Book::first()->copies);
    }

    public function test_the_number_of_copies_is_validated(): void
    {
        $payload = ['content' => BookContent::Both->value, 'size' => BookSize::Portrait->value];

        $this->actingAs($this->owner)
            ->from(route('dashboard.book'))
            ->put(route('dashboard.book.update'), [...$payload, 'copies' => 0])
            ->assertSessionHasErrors('copies');

        $this->actingAs($this->owner)
            ->from(route('dashboard.book'))
            ->put(route('dashboard.book.update'), [...$payload, 'copies' => Book::MAX_COPIES + 1])
            ->assertSessionHasErrors('copies');
    }

    public function test_the_preview_endpoint_returns_just_the_pages(): void
    {
        Memory::factory()->for($this->memorial)->create();

        $this->actingAs($this->owner)
            ->get(route('dashboard.book.preview', ['content' => 'memories', 'size' => 'square_21']))
            ->assertOk()
            ->assertSee('data-book-page', escape: false)
            ->assertDontSee('panel-tabs', escape: false);
    }

    public function test_only_approved_memories_reach_the_book(): void
    {
        Memory::factory()->for($this->memorial)->create(['body_plain' => 'זיכרון מאושר']);
        Memory::factory()->for($this->memorial)->pending()->create(['body_plain' => 'זיכרון שממתין']);

        $pages = app(BookComposer::class)->compose($this->memorial, BookContent::Memories, BookSize::Portrait);
        $bodies = collect($pages)->pluck('body')->filter()->implode(' ');

        $this->assertStringContainsString('זיכרון מאושר', $bodies);
        $this->assertStringNotContainsString('זיכרון שממתין', $bodies);
    }

    public function test_the_content_choice_decides_what_is_composed(): void
    {
        Memory::factory()->for($this->memorial)->create();
        $this->addPhotos(3);
        $this->memorial->refresh();

        $composer = app(BookComposer::class);
        $types = fn (BookContent $c) => collect($composer->compose($this->memorial, $c, BookSize::Portrait))->pluck('type');

        $this->assertTrue($types(BookContent::Memories)->contains('memory'));
        $this->assertFalse($types(BookContent::Memories)->contains('photos'));

        $this->assertTrue($types(BookContent::Photos)->contains('photos'));
        $this->assertFalse($types(BookContent::Photos)->contains('memory'));

        $both = $types(BookContent::Both);
        $this->assertTrue($both->contains('memory'));
        $this->assertTrue($both->contains('photos'));
    }

    public function test_a_smaller_page_needs_more_pages_for_the_same_text(): void
    {
        Memory::factory()->for($this->memorial)->create([
            'body_plain' => str_repeat('מילה ', 2000),
            'body' => '<p>'.str_repeat('מילה ', 2000).'</p>',
        ]);

        $composer = app(BookComposer::class);
        $small = count($composer->compose($this->memorial, BookContent::Memories, BookSize::Square));
        $large = count($composer->compose($this->memorial, BookContent::Memories, BookSize::Portrait));

        $this->assertGreaterThan($large, $small);
    }

    public function test_the_page_count_is_always_a_multiple_of_four(): void
    {
        Memory::factory()->for($this->memorial)->count(5)->create();
        $this->addPhotos(7);
        $this->memorial->refresh();

        foreach (BookSize::cases() as $size) {
            $count = count(app(BookComposer::class)->compose($this->memorial, BookContent::Both, $size));
            $this->assertSame(0, $count % 4, "‎{$size->value} composed {$count} pages");
        }
    }

    public function test_the_builder_is_private(): void
    {
        $this->get(route('dashboard.book'))->assertRedirect(route('login'));

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('dashboard.book'))->assertRedirect();
    }
}
