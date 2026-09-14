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

    public function test_a_page_can_be_rewritten(): void
    {
        Memory::factory()->for($this->memorial)->create();

        $this->actingAs($this->owner)
            ->put(route('dashboard.book.page.update'), [
                'key' => 'cover',
                'title' => 'שם אחר לכריכה',
                'size' => BookSize::Portrait->value,
                'content' => BookContent::Both->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(['title' => 'שם אחר לכריכה'], Book::first()->overrides['cover']);

        $cover = collect(app(BookComposer::class)->compose(
            $this->memorial->fresh(), BookContent::Both, BookSize::Portrait, Book::first()->overrides
        ))->firstWhere('key', 'cover');

        $this->assertSame('שם אחר לכריכה', $cover->title);
    }

    public function test_text_put_back_to_the_original_stops_being_an_override(): void
    {
        Memory::factory()->for($this->memorial)->create();
        $payload = ['key' => 'cover', 'size' => BookSize::Portrait->value, 'content' => BookContent::Both->value];

        $this->actingAs($this->owner)->put(route('dashboard.book.page.update'), [...$payload, 'title' => 'שם אחר']);
        $this->assertArrayHasKey('cover', Book::first()->overrides);

        $this->actingAs($this->owner)->put(route('dashboard.book.page.update'), [...$payload, 'title' => 'אברהם כוכב']);
        $this->assertArrayNotHasKey('cover', Book::first()->overrides ?? []);
    }

    public function test_an_edit_survives_a_change_of_size(): void
    {
        Memory::factory()->for($this->memorial)->create();

        $this->actingAs($this->owner)->put(route('dashboard.book.page.update'), [
            'key' => 'cover',
            'title' => 'שם אחר',
            'size' => BookSize::Portrait->value,
            'content' => BookContent::Both->value,
        ]);

        foreach (BookSize::cases() as $size) {
            $cover = collect(app(BookComposer::class)->compose(
                $this->memorial, BookContent::Both, $size, Book::first()->overrides
            ))->firstWhere('key', 'cover');

            $this->assertSame('שם אחר', $cover->title, "lost on {$size->value}");
        }
    }

    public function test_editing_an_unknown_page_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->put(route('dashboard.book.page.update'), ['key' => 'memory:999:0', 'title' => 'x'])
            ->assertNotFound();
    }

    public function test_every_page_carries_a_key_except_the_blanks(): void
    {
        Memory::factory()->for($this->memorial)->count(3)->create();
        $this->addPhotos(5);
        $this->memorial->refresh();

        $pages = app(BookComposer::class)->compose($this->memorial, BookContent::Both, BookSize::Portrait);
        $keys = collect($pages)->reject(fn ($p) => $p->type === 'blank')->pluck('key');

        $this->assertTrue($keys->every(fn ($k) => $k !== ''), 'a page is missing its key');
        $this->assertSame($keys->count(), $keys->unique()->count(), 'two pages share a key');
    }

    public function test_the_owner_can_drop_every_edit(): void
    {
        Memory::factory()->for($this->memorial)->create();
        $this->actingAs($this->owner)->put(route('dashboard.book.page.update'), [
            'key' => 'cover', 'title' => 'שם אחר',
            'size' => BookSize::Portrait->value, 'content' => BookContent::Both->value,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('dashboard.book.pages.reset'))
            ->assertRedirect();

        $this->assertNull(Book::first()->overrides);
    }

    public function test_a_photo_can_be_taken_out_of_the_book_and_put_back(): void
    {
        $this->addPhotos(3);
        $this->memorial->refresh();
        $key = 'gallery:'.$this->memorial->images->first()->id;
        $offered = $this->memorial->images->map(fn ($i) => "gallery:{$i->id}")->all();

        $this->actingAs($this->owner)
            ->put(route('dashboard.book.photos.update'), ['offered' => $offered, 'remove' => [$key]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame([$key], Book::first()->excluded());

        $shown = collect(app(BookComposer::class)->compose(
            $this->memorial, BookContent::Photos, BookSize::Portrait, [], Book::first()->excluded()
        ))->flatMap(fn ($p) => collect($p->images)->pluck('key'))->filter()->all();

        $this->assertNotContains($key, $shown);
        $this->assertCount(2, $shown);

        // Offering it without listing it for removal puts it back.
        $this->actingAs($this->owner)
            ->put(route('dashboard.book.photos.update'), ['offered' => $offered, 'remove' => []]);

        $this->assertSame([], Book::first()->excluded());
    }

    public function test_removing_photos_on_one_page_leaves_other_pages_alone(): void
    {
        $this->addPhotos(12);
        $this->memorial->refresh();
        $all = $this->memorial->images->map(fn ($i) => "gallery:{$i->id}")->all();

        $book = Book::create(['memorial_id' => $this->memorial->id, 'excluded_images' => [$all[9]]]);

        // A page holding the first four photos submits only those four.
        $book->setPhotoExclusions(array_slice($all, 0, 4), [$all[0]]);

        $this->assertEqualsCanonicalizing([$all[9], $all[0]], $book->fresh()->excluded());
    }

    public function test_the_photo_endpoint_rejects_a_malformed_key(): void
    {
        $this->actingAs($this->owner)
            ->from(route('dashboard.book'))
            ->put(route('dashboard.book.photos.update'), ['offered' => ['users:1'], 'remove' => []])
            ->assertSessionHasErrors('offered.0');
    }

    public function test_resetting_also_puts_the_photos_back(): void
    {
        $this->addPhotos(2);
        $this->memorial->refresh();
        Book::create([
            'memorial_id' => $this->memorial->id,
            'excluded_images' => ['gallery:'.$this->memorial->images->first()->id],
        ]);

        $this->actingAs($this->owner)->delete(route('dashboard.book.pages.reset'))->assertRedirect();

        $this->assertSame([], Book::first()->excluded());
    }

    public function test_the_builder_is_private(): void
    {
        $this->get(route('dashboard.book'))->assertRedirect(route('login'));

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('dashboard.book'))->assertRedirect();
    }
}
