<?php

namespace Tests\Feature;

use App\Models\Memorial;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'phone' => '+972501112222']);
        Memorial::factory()->for($user)->create(['slug' => 'demo']);

        return $user;
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('כניסה');
    }

    public function test_it_sends_a_code_to_an_email(): void
    {
        $this->user();

        $this->post('/login/send', ['identifier' => 'owner@example.com'])
            ->assertRedirect(route('login.verify'));

        $this->assertDatabaseHas('otp_codes', ['identifier' => 'owner@example.com', 'channel' => 'email', 'purpose' => 'login']);
    }

    public function test_it_sends_a_code_by_sms_when_sms_is_configured(): void
    {
        $this->user();
        app(\App\Services\Settings\SettingsRepository::class)->setMany(['sms.enabled' => '1', 'sms.driver' => 'log']);

        $this->post('/login/send', ['identifier' => '050-111-2222', 'country_code' => '972'])
            ->assertRedirect(route('login.verify'));

        $this->assertDatabaseHas('otp_codes', ['identifier' => '+972501112222', 'channel' => 'sms', 'purpose' => 'login']);
    }

    public function test_a_phone_login_falls_back_to_email_when_sms_is_off(): void
    {
        $this->user();

        $this->post('/login/send', ['identifier' => '050-111-2222', 'country_code' => '972'])
            ->assertRedirect(route('login.verify'));

        $this->assertDatabaseHas('otp_codes', ['identifier' => 'owner@example.com', 'channel' => 'email']);
    }

    public function test_an_unknown_identifier_is_rejected(): void
    {
        $this->from('/login')
            ->post('/login/send', ['identifier' => 'nobody@example.com'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('identifier');
    }

    public function test_a_valid_code_logs_the_user_in(): void
    {
        $user = $this->user();
        $this->post('/login/send', ['identifier' => 'owner@example.com']);

        $code = app(OtpService::class)->lastPlainCode;

        $this->post('/login/verify', ['code' => $code])
            ->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_code_can_only_be_used_once(): void
    {
        $this->user();
        $this->post('/login/send', ['identifier' => 'owner@example.com']);
        $code = app(OtpService::class)->lastPlainCode;

        $this->post('/login/verify', ['code' => $code]);
        $this->post('/logout');

        $this->post('/login/send', ['identifier' => 'owner@example.com']);
        $this->from(route('login.verify'))
            ->post('/login/verify', ['code' => $code === '111111' ? '222222' : '111111'])
            ->assertSessionHasErrors('code');
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = $this->user();
        $this->post('/login/send', ['identifier' => 'owner@example.com']);
        $code = app(OtpService::class)->lastPlainCode;

        $this->travel(config('endless.otp.ttl_minutes') + 1)->minutes();

        $this->from(route('login.verify'))
            ->post('/login/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_too_many_wrong_attempts_burn_the_code(): void
    {
        $this->user();
        $this->post('/login/send', ['identifier' => 'owner@example.com']);
        $code = app(OtpService::class)->lastPlainCode;

        for ($i = 0; $i <= config('endless.otp.max_attempts'); $i++) {
            $this->post('/login/verify', ['code' => '000000']);
        }

        $this->from(route('login.verify'))
            ->post('/login/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_guests_are_redirected_away_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_logout_works(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_the_otp_is_stored_hashed(): void
    {
        $this->user();
        $this->post('/login/send', ['identifier' => 'owner@example.com']);

        $code = app(OtpService::class)->lastPlainCode;
        $stored = \App\Models\OtpCode::latest('id')->first();

        $this->assertNotSame($code, $stored->code_hash);
        $this->assertTrue(Hash::check($code, $stored->code_hash));
    }
}
