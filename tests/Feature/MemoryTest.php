<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\MemoryStatus;
use App\Models\Memorial;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemoryTest extends TestCase
{
    use RefreshDatabase;

    protected Memorial $memorial;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->memorial = Memorial::factory()->for($this->owner)->create(['slug' => 'demo']);
    }

    protected function shareUrl(): string
    {
        return route('memories.create', [$this->memorial, $this->memorial->share_token]);
    }

    public function test_the_share_form_opens_with_a_valid_token(): void
    {
        $this->get($this->shareUrl())->assertOk()->assertSee($this->memorial->full_name);
    }

    public function test_a_wrong_token_is_a_404(): void
    {
        $this->get(route('memories.create', [$this->memorial, 'not-the-token']))->assertNotFound();
    }

    public function test_a_memory_is_submitted_and_waits_for_approval(): void
    {
        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>זיכרון ראשון</p>',
        ])->assertRedirect($this->shareUrl());

        $memory = Memory::first();
        $this->assertSame('יעל', $memory->author_name);
        $this->assertSame(MemoryStatus::Pending, $memory->status);
        $this->assertSame('זיכרון ראשון', $memory->body_plain);
    }

    public function test_it_publishes_immediately_when_approval_is_off(): void
    {
        $this->memorial->update(['require_approval' => false]);

        $this->post($this->shareUrl(), ['author_name' => 'יעל', 'body' => '<p>זיכרון</p>']);

        $this->assertSame(MemoryStatus::Approved, Memory::first()->status);
    }

    public function test_dangerous_html_is_stripped(): void
    {
        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>שלום<script>alert(1)</script></p><img src=x onerror=alert(1)><a href="javascript:alert(1)">קישור</a><b onclick="x()">מודגש</b>',
        ]);

        $body = Memory::first()->body;

        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onerror', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertStringContainsString('שלום', $body);
        $this->assertStringContainsString('<b>מודגש</b>', $body);
    }

    public function test_allowed_formatting_survives(): void
    {
        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p><strong>חזק</strong> <em>נטוי</em></p><ul><li>פריט</li></ul><blockquote>ציטוט</blockquote><a href="https://example.com">קישור</a>',
        ]);

        $body = Memory::first()->body;

        $this->assertStringContainsString('<strong>חזק</strong>', $body);
        $this->assertStringContainsString('<li>פריט</li>', $body);
        $this->assertStringContainsString('<blockquote>ציטוט</blockquote>', $body);
        $this->assertStringContainsString('href="https://example.com"', $body);
        $this->assertStringContainsString('rel="noopener nofollow"', $body);
    }

    public function test_images_are_stored(): void
    {
        Storage::fake('public');

        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>עם תמונות</p>',
            'media' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.jpg', 600, 800)],
        ]);

        $memory = Memory::first();
        $this->assertCount(2, $memory->images);
        Storage::disk('public')->assertExists($memory->images->first()->path);
        Storage::disk('public')->assertExists($memory->images->first()->thumb_path);
    }

    public function test_a_video_can_be_attached(): void
    {
        Storage::fake('public');

        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>עם סרטון</p>',
            'media' => [UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4')],
        ])->assertSessionHasNoErrors();

        $memory = Memory::first();
        $video = $memory->media->first();

        $this->assertCount(1, $memory->media);
        $this->assertTrue($video->is_video);
        $this->assertSame(MediaType::Video, $video->type);
        $this->assertStringEndsWith('.mp4', $video->path);
        Storage::disk('public')->assertExists($video->path);
    }

    public function test_photos_and_a_video_can_be_mixed(): void
    {
        Storage::fake('public');

        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>גם וגם</p>',
            'media' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4'),
                UploadedFile::fake()->image('b.jpg', 600, 800),
            ],
        ])->assertSessionHasNoErrors();

        $memory = Memory::first();

        $this->assertCount(3, $memory->media);
        $this->assertCount(2, $memory->images);
        $this->assertCount(1, $memory->videos);
        // The card image is the first photo, never the video.
        $this->assertFalse($memory->cover->is_video);
    }

    public function test_a_video_becomes_the_cover_when_there_is_no_photo(): void
    {
        Storage::fake('public');

        $this->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>רק סרטון</p>',
            'media' => [UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4')],
        ]);

        $this->assertTrue(Memory::first()->cover->is_video);
    }

    public function test_an_oversized_video_is_rejected(): void
    {
        Storage::fake('public');

        $this->from($this->shareUrl())->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>סרטון ענק</p>',
            'media' => [UploadedFile::fake()->create('huge.mp4', 70000, 'video/mp4')],
        ])->assertSessionHasErrors('media.0');

        $this->assertSame(0, Memory::count());
    }

    public function test_an_unsupported_file_type_is_rejected(): void
    {
        Storage::fake('public');

        $this->from($this->shareUrl())->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>קובץ אסור</p>',
            'media' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('media.0');
    }

    public function test_the_number_of_files_is_capped(): void
    {
        Storage::fake('public');

        $files = [];
        for ($i = 0; $i < 12; $i++) {
            $files[] = UploadedFile::fake()->image("p{$i}.jpg", 400, 300);
        }

        $this->from($this->shareUrl())->post($this->shareUrl(), [
            'author_name' => 'יעל',
            'body' => '<p>יותר מדי</p>',
            'media' => $files,
        ])->assertSessionHasErrors('media');
    }

    /* ------------------------------------------------ browsing between memories */

    public function test_a_memory_links_to_the_previous_and_next_one(): void
    {
        $oldest = Memory::factory()->for($this->memorial)->create(['author_name' => 'ראשון', 'created_at' => now()->subDays(3)]);
        $middle = Memory::factory()->for($this->memorial)->create(['author_name' => 'אמצעי', 'created_at' => now()->subDays(2)]);
        $newest = Memory::factory()->for($this->memorial)->create(['author_name' => 'אחרון', 'created_at' => now()->subDay()]);

        $response = $this->get(route('memories.show', [$this->memorial, $middle]));

        $response->assertOk()
            ->assertSee(route('memories.show', [$this->memorial, $newest]), false)
            ->assertSee(route('memories.show', [$this->memorial, $oldest]), false)
            ->assertSee('הזיכרון הקודם')
            ->assertSee('הזיכרון הבא')
            ->assertSee('זיכרון 2 מתוך 3');
    }

    public function test_the_newest_memory_has_no_previous_link(): void
    {
        Memory::factory()->for($this->memorial)->create(['created_at' => now()->subDays(2)]);
        $newest = Memory::factory()->for($this->memorial)->create(['created_at' => now()->subDay()]);

        $this->get(route('memories.show', [$this->memorial, $newest]))
            ->assertOk()
            ->assertDontSee('הזיכרון הקודם')
            ->assertSee('הזיכרון הבא')
            ->assertSee('זיכרון 1 מתוך 2');
    }

    public function test_the_oldest_memory_has_no_next_link(): void
    {
        $oldest = Memory::factory()->for($this->memorial)->create(['created_at' => now()->subDays(2)]);
        Memory::factory()->for($this->memorial)->create(['created_at' => now()->subDay()]);

        $this->get(route('memories.show', [$this->memorial, $oldest]))
            ->assertOk()
            ->assertSee('הזיכרון הקודם')
            ->assertDontSee('הזיכרון הבא')
            ->assertSee('זיכרון 2 מתוך 2');
    }

    public function test_navigation_skips_memories_that_are_not_approved(): void
    {
        $oldest = Memory::factory()->for($this->memorial)->create(['author_name' => 'ישן', 'created_at' => now()->subDays(3)]);
        Memory::factory()->for($this->memorial)->pending()->create(['author_name' => 'ממתין', 'created_at' => now()->subDays(2)]);
        $newest = Memory::factory()->for($this->memorial)->create(['author_name' => 'חדש', 'created_at' => now()->subDay()]);

        $this->get(route('memories.show', [$this->memorial, $oldest]))
            ->assertOk()
            ->assertSee(route('memories.show', [$this->memorial, $newest]), false)
            ->assertSee('זיכרון 2 מתוך 2');
    }

    public function test_the_lone_memory_shows_no_navigation(): void
    {
        $only = Memory::factory()->for($this->memorial)->create();

        $this->get(route('memories.show', [$this->memorial, $only]))
            ->assertOk()
            ->assertDontSee('הזיכרון הקודם')
            ->assertDontSee('הזיכרון הבא');
    }

    public function test_it_validates_the_body(): void
    {
        $this->from($this->shareUrl())
            ->post($this->shareUrl(), ['author_name' => 'יעל', 'body' => '<p>  </p>'])
            ->assertSessionHasErrors('body');
    }

    public function test_pending_memories_are_hidden_from_the_public_page(): void
    {
        $pending = Memory::factory()->for($this->memorial)->pending()->create(['author_name' => 'ממתין']);
        $approved = Memory::factory()->for($this->memorial)->create(['author_name' => 'מאושר']);

        $response = $this->get(route('memorials.show', $this->memorial));

        $response->assertOk()->assertSee('מאושר')->assertDontSee('ממתין');
        $this->get(route('memories.show', [$this->memorial, $pending]))->assertNotFound();
        $this->get(route('memories.show', [$this->memorial, $approved]))->assertOk();
    }

    public function test_the_owner_can_preview_a_pending_memory(): void
    {
        $pending = Memory::factory()->for($this->memorial)->pending()->create();

        $this->actingAs($this->owner)
            ->get(route('memories.show', [$this->memorial, $pending]))
            ->assertOk();
    }

    public function test_the_owner_approves_and_rejects(): void
    {
        $memory = Memory::factory()->for($this->memorial)->pending()->create();

        $this->actingAs($this->owner)->patch(route('dashboard.memories.approve', $memory));
        $this->assertSame(MemoryStatus::Approved, $memory->fresh()->status);

        $this->actingAs($this->owner)->patch(route('dashboard.memories.reject', $memory));
        $this->assertSame(MemoryStatus::Rejected, $memory->fresh()->status);
    }

    public function test_another_user_cannot_moderate(): void
    {
        $memory = Memory::factory()->for($this->memorial)->pending()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->patch(route('dashboard.memories.approve', $memory))
            ->assertForbidden();

        $this->assertSame(MemoryStatus::Pending, $memory->fresh()->status);
    }

    public function test_the_feed_endpoint_returns_the_next_page(): void
    {
        Memory::factory()->for($this->memorial)->count(14)->create();

        $response = $this->getJson(route('memorials.feed', $this->memorial).'?page=2');

        $response->assertOk()->assertJsonStructure(['html', 'hasMore', 'nextPage', 'total']);
        $this->assertSame(14, $response->json('total'));
        $this->assertFalse($response->json('hasMore'));
    }

    public function test_the_owner_can_regenerate_the_share_link(): void
    {
        $old = $this->memorial->share_token;

        $this->actingAs($this->owner)->post(route('dashboard.share.regenerate'));

        $this->assertNotSame($old, $this->memorial->fresh()->share_token);
        $this->get(route('memories.create', [$this->memorial, $old]))->assertNotFound();
    }

    public function test_the_memorial_page_is_not_indexed(): void
    {
        $this->get(route('memorials.show', $this->memorial))
            ->assertOk()
            ->assertSee('noindex', false);
    }
}
