<?php

namespace Tests\Feature;

use App\Models\Memorial;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Memorial $memorial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['first_name' => 'אור']);
        $this->memorial = Memorial::factory()->for($this->owner)->create(['slug' => 'demo', 'first_name' => 'אברהם', 'last_name' => 'כוכב']);
    }

    public function test_the_overview_renders(): void
    {
        Memory::factory()->for($this->memorial)->count(3)->create();
        Memory::factory()->for($this->memorial)->pending()->count(2)->create();

        $this->actingAs($this->owner)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('אור')
            ->assertSee('אברהם כוכב')
            ->assertSee('ממתינים לאישור');
    }

    public function test_all_dashboard_pages_render(): void
    {
        foreach ([
            route('dashboard.index'),
            route('dashboard.memorial.edit'),
            route('dashboard.memories.index'),
            route('dashboard.memories.create'),
            route('dashboard.share'),
            route('dashboard.account.edit'),
        ] as $url) {
            $this->actingAs($this->owner)->get($url)->assertOk();
        }
    }

    public function test_the_owner_updates_the_memorial(): void
    {
        $this->actingAs($this->owner)->put(route('dashboard.memorial.update'), [
            'first_name' => 'אברהם',
            'last_name' => 'כוכב',
            'gender' => 'male',
            'subtitle' => '',
            'birth_date' => '1951-06-19',
            'death_date' => '2022-08-15',
            'religion' => 'jewish',
            'biography_title' => 'איש משפחה',
            'biography' => '<p>טקסט <script>alert(1)</script>נקי</p>',
            'quote' => 'ציטוט',
            'quote_name' => 'אברהם',
            'founder_name' => 'אור',
            'slug' => 'avraham-kochav',
            'visibility' => 'private',
            'require_approval' => '1',
        ])->assertSessionHasNoErrors();

        $memorial = $this->memorial->fresh();
        $this->assertSame('avraham-kochav', $memorial->slug);
        $this->assertSame('לזכרו של יקירנו', $memorial->subtitle);
        $this->assertStringNotContainsString('<script', $memorial->biography);
        $this->assertStringContainsString('נקי', $memorial->biography);
        $this->assertTrue($memorial->require_approval);
    }

    public function test_a_taken_slug_is_rejected(): void
    {
        Memorial::factory()->create(['slug' => 'taken']);

        $this->actingAs($this->owner)->put(route('dashboard.memorial.update'), [
            'first_name' => 'אברהם',
            'gender' => 'male',
            'religion' => 'jewish',
            'slug' => 'taken',
            'visibility' => 'private',
        ])->assertSessionHasErrors('slug');
    }

    public function test_gallery_upload_and_delete(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->post(route('dashboard.gallery.store'), [
            'images' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ])->assertSessionHasNoErrors();

        $image = $this->memorial->fresh()->images->first();
        $this->assertNotNull($image);
        Storage::disk('public')->assertExists($image->path);

        $this->actingAs($this->owner)->delete(route('dashboard.gallery.destroy', $image));

        $this->assertSame(0, $this->memorial->fresh()->images()->count());
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_the_owner_uploads_a_portrait(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->post(route('dashboard.memorial.media.store', 'portrait_image'), [
            'file' => UploadedFile::fake()->image('p.jpg', 600, 800),
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($this->memorial->fresh()->portrait_image_path);
    }

    public function test_the_owner_adds_a_memory_that_is_published_at_once(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.memories.store'), [
            'author_name' => 'אור כוכב',
            'body' => '<p>זיכרון של הבעלים</p>',
        ])->assertSessionHasNoErrors();

        $memory = Memory::first();
        $this->assertTrue($memory->is_approved);
        $this->assertSame('owner', $memory->submitted_via);
    }

    public function test_the_account_can_be_updated(): void
    {
        $this->actingAs($this->owner)->put(route('dashboard.account.update'), [
            'first_name' => 'אור',
            'last_name' => 'כוכב',
            'email' => 'new@example.com',
            'country_code' => '972',
            'phone' => '052-7654321',
        ])->assertSessionHasNoErrors();

        $user = $this->owner->fresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('+972527654321', $user->phone);
    }

    public function test_a_stranger_cannot_open_another_dashboard(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('dashboard.index'))
            ->assertRedirect(route('register'));
    }

    public function test_a_stranger_cannot_edit_another_memorial(): void
    {
        $stranger = User::factory()->create();
        Memorial::factory()->for($stranger)->create();

        $image = $this->memorial->images()->create(['path' => 'x.webp', 'sort_order' => 0]);

        $this->actingAs($stranger)
            ->delete(route('dashboard.gallery.destroy', $image))
            ->assertForbidden();
    }
}
