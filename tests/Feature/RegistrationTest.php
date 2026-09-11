<?php

namespace Tests\Feature;

use App\Models\Memorial;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'deceased_first_name' => 'אברהם',
            'deceased_last_name' => 'כוכב',
            'deceased_birth_date' => '1951-06-19',
            'deceased_death_date' => '2022-08-15',
            'deceased_gender' => 'male',
            'deceased_religion' => 'jewish',
            'deceased_bio' => "שורה ראשונה\n\nשורה שנייה",
            'creator_first_name' => 'אור',
            'creator_last_name' => 'כוכב',
            'creator_email' => 'or@example.com',
            'country_code' => '972',
            'creator_phone' => '050-1234567',
            'terms' => '1',
        ], $overrides);
    }

    public function test_the_registration_form_is_reachable(): void
    {
        $this->get('/register')->assertOk()->assertSee('יצירת עמוד');
    }

    public function test_it_creates_a_user_and_a_memorial_then_asks_for_a_code(): void
    {
        $response = $this->post('/register', $this->payload());

        $response->assertRedirect(route('register.verify'));

        $user = User::firstWhere('email', 'or@example.com');
        $this->assertNotNull($user);
        $this->assertSame('+972501234567', $user->phone);

        $memorial = Memorial::firstWhere('user_id', $user->id);
        $this->assertNotNull($memorial);
        $this->assertSame('abrhm-kvkb', $memorial->slug);
        $this->assertSame('לזכרו של יקירנו', $memorial->subtitle);
        $this->assertStringContainsString('<p>שורה ראשונה</p>', $memorial->biography);
        $this->assertNotNull($memorial->share_token);

        // Not logged in until the code is verified.
        $this->assertGuest();
        $this->assertDatabaseHas('otp_codes', ['user_id' => $user->id, 'purpose' => 'register']);
    }

    public function test_it_stores_uploaded_photos_and_sets_the_portrait(): void
    {
        Storage::fake('public');

        $this->post('/register', $this->payload([
            'deceased_image' => [
                UploadedFile::fake()->image('one.jpg', 900, 700),
                UploadedFile::fake()->image('two.jpg', 700, 900),
            ],
        ]))->assertRedirect(route('register.verify'));

        $memorial = Memorial::first();
        $this->assertCount(2, $memorial->images);
        $this->assertNotNull($memorial->portrait_image_path);
        Storage::disk('public')->assertExists($memorial->images->first()->path);
        Storage::disk('public')->assertExists($memorial->portrait_image_path);
    }

    public function test_verifying_the_code_logs_the_user_in(): void
    {
        $this->post('/register', $this->payload());

        $code = app(OtpService::class)->lastPlainCode;
        $this->assertNotNull($code);

        $this->post('/register/verify', ['code' => $code])
            ->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticated();
        $this->assertNotNull(User::first()->last_login_at);
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        $this->post('/register', $this->payload());

        $this->from(route('register.verify'))
            ->post('/register/verify', ['code' => '000000'])
            ->assertRedirect(route('register.verify'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_an_existing_email_is_sent_to_the_login_page(): void
    {
        User::factory()->create(['email' => 'or@example.com']);

        $this->post('/register', $this->payload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertSame(1, User::count());
    }

    public function test_it_validates_required_fields(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['deceased_first_name', 'deceased_death_date', 'creator_email', 'creator_phone', 'terms']);
    }

    public function test_it_rejects_an_invalid_phone_number(): void
    {
        $this->post('/register', $this->payload(['creator_phone' => '12']))
            ->assertSessionHasErrors('creator_phone_e164');
    }
}
