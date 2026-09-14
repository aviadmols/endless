<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Memorial;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('אוספים זיכרונות')
            ->assertSee('איך זה עובד')
            ->assertSee('סיפורים שנשארים איתכם');
    }

    public function test_the_shiryon_landing_page_renders(): void
    {
        $this->get('/shiryon')->assertOk()->assertSee('משפחה יקרה');
    }

    public function test_a_lead_is_stored(): void
    {
        $this->post('/leads', [
            'name' => 'משפחת כוכב',
            'email' => 'family@example.com',
            'phone' => '050-1234567',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['email' => 'family@example.com', 'source' => 'home']);
    }

    public function test_the_lead_form_validates(): void
    {
        $this->from('/shiryon')->post('/leads', ['name' => ''])->assertSessionHasErrors(['name', 'email']);
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        $this->from('/shiryon')->post('/leads', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Lead::count());
    }

    public function test_the_memorial_page_renders_every_section(): void
    {
        $memorial = Memorial::factory()->create([
            'slug' => 'demo',
            'first_name' => 'אברהם',
            'last_name' => 'כוכב',
            'gender' => \App\Enums\Gender::Male,
            'subtitle' => 'לזכרו של יקירנו',
            'biography_title' => 'איש משפחה',
            'biography' => '<p>ביוגרפיה</p>',
            'quote' => 'ציטוט חשוב',
            'quote_name' => 'אברהם',
            'founder_name' => 'אור',
        ]);
        Memory::factory()->for($memorial)->count(3)->create();

        $this->get(route('memorials.show', $memorial))
            ->assertOk()
            ->assertSee('אברהם כוכב')
            ->assertSee('לזכרו של יקירנו')
            ->assertSee('איש משפחה')
            ->assertSee('ביוגרפיה')
            ->assertSee('ציטוט חשוב')
            ->assertSee('הוקם ע״י אור');
    }

    public function test_an_unknown_memorial_is_a_404(): void
    {
        $this->get('/m/does-not-exist')->assertNotFound();
    }

    public function test_the_view_counter_increases_once_per_visitor(): void
    {
        $memorial = Memorial::factory()->create(['slug' => 'demo']);

        $this->get(route('memorials.show', $memorial));
        $this->get(route('memorials.show', $memorial));

        $this->assertSame(1, $memorial->fresh()->views_count);
    }

    public function test_the_owner_does_not_inflate_the_counter(): void
    {
        $owner = User::factory()->create();
        $memorial = Memorial::factory()->for($owner)->create(['slug' => 'demo']);

        $this->actingAs($owner)->get(route('memorials.show', $memorial));

        $this->assertSame(0, $memorial->fresh()->views_count);
    }

    public function test_dates_are_displayed_newest_first_like_the_reference(): void
    {
        $memorial = Memorial::factory()->create([
            'slug' => 'demo',
            'birth_date' => '1951-06-19',
            'death_date' => '2022-08-15',
            'dates_text' => null,
        ]);

        $this->assertSame('15.8.2022 – 19.6.1951', $memorial->dates_display);
    }

    public function test_free_text_dates_win(): void
    {
        $memorial = Memorial::factory()->create(['dates_text' => 'תש״ה – תשפ״ב']);

        $this->assertSame('תש״ה – תשפ״ב', $memorial->dates_display);
    }
}
