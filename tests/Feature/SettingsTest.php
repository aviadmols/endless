<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Mail\DynamicMailConfigurator;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\Drivers\Sms019Driver;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_a_normal_user_cannot_open_the_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_the_admin_pages_render(): void
    {
        $admin = $this->admin();

        foreach (['general', 'mail', 'sms', 'landing'] as $group) {
            $this->actingAs($admin)->get(route('admin.settings.edit', $group))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.memorials.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.leads.index'))->assertOk();
    }

    public function test_mail_settings_are_saved_and_the_password_is_encrypted(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'mail'), [
            'mail__enabled' => '1',
            'mail__host' => 'smtp.example.com',
            'mail__port' => '587',
            'mail__encryption' => 'tls',
            'mail__username' => 'user@example.com',
            'mail__password' => 'super-secret',
            'mail__from_address' => 'hello@example.com',
            'mail__from_name' => 'Endless',
        ])->assertSessionHasNoErrors();

        $settings = app(SettingsRepository::class);
        $settings->flush();

        $this->assertSame('smtp.example.com', $settings->get('mail.host'));
        $this->assertSame('super-secret', $settings->get('mail.password'));
        $this->assertTrue($settings->mailConfigured());

        $row = Setting::find('mail.password');
        $this->assertTrue($row->is_encrypted);
        $this->assertNotSame('super-secret', $row->value);
        $this->assertSame('super-secret', Crypt::decryptString($row->value));
    }

    public function test_an_empty_password_keeps_the_stored_one(): void
    {
        $settings = app(SettingsRepository::class);
        $settings->set('mail.password', 'keep-me');

        $this->actingAs($this->admin())->put(route('admin.settings.update', 'mail'), [
            'mail__enabled' => '1',
            'mail__host' => 'smtp.example.com',
            'mail__port' => '587',
            'mail__encryption' => 'tls',
            'mail__username' => 'user@example.com',
            'mail__password' => '',
            'mail__from_address' => 'hello@example.com',
            'mail__from_name' => 'Endless',
        ]);

        $settings->flush();
        $this->assertSame('keep-me', $settings->get('mail.password'));
    }

    public function test_the_smtp_settings_are_applied_to_the_mailer(): void
    {
        $settings = app(SettingsRepository::class);
        $settings->setMany([
            'mail.enabled' => '1',
            'mail.host' => 'smtp.example.com',
            'mail.port' => '465',
            'mail.encryption' => 'ssl',
            'mail.username' => 'u',
            'mail.password' => 'p',
            'mail.from_address' => 'hello@example.com',
            'mail.from_name' => 'Endless',
        ]);

        app(DynamicMailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('hello@example.com', config('mail.from.address'));
    }

    public function test_sms_settings_drive_the_019_driver(): void
    {
        $settings = app(SettingsRepository::class);
        $settings->setMany([
            'sms.enabled' => '1',
            'sms.driver' => '019sms',
            'sms.019.username' => 'endless',
            'sms.019.token' => 'tok_123',
            'sms.019.source' => 'Endless',
        ]);

        $manager = app(SmsManager::class);
        $this->assertTrue($manager->isConfigured());
        $this->assertInstanceOf(Sms019Driver::class, $manager->driver());
    }

    public function test_the_019_request_has_the_documented_shape(): void
    {
        Http::fake([
            '019sms.co.il/*' => Http::response(['status' => 0, 'message' => 'OK'], 200),
        ]);

        $driver = new Sms019Driver('endless', 'tok_123', null, 'Endless');
        $result = $driver->send('+972501234567', 'קוד: 123456');

        $this->assertTrue($result->ok);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer tok_123')
                && $body['sms']['user']['username'] === 'endless'
                && $body['sms']['source'] === 'Endless'
                && $body['sms']['destinations']['phone'][0]['_'] === '0501234567'
                && str_contains($body['sms']['message'], '123456');
        });
    }

    public function test_a_019_error_is_reported(): void
    {
        Http::fake([
            '019sms.co.il/*' => Http::response(['status' => 1, 'message' => 'Invalid user'], 200),
        ]);

        $result = (new Sms019Driver('endless', 'bad', null, 'Endless'))->send('+972501234567', 'x');

        $this->assertFalse($result->ok);
        $this->assertStringContainsString('Invalid user', $result->message);
    }

    public function test_legacy_accounts_authenticate_with_a_password(): void
    {
        $payload = (new Sms019Driver('endless', null, 'pw', 'Endless'))->payload('+972501234567', 'x');

        $this->assertSame('pw', $payload['sms']['user']['password']);
    }

    public function test_the_sms_test_button_reports_success(): void
    {
        Http::fake(['019sms.co.il/*' => Http::response(['status' => 0, 'message' => 'OK'], 200)]);

        app(SettingsRepository::class)->setMany([
            'sms.enabled' => '1', 'sms.driver' => '019sms',
            'sms.019.username' => 'endless', 'sms.019.token' => 'tok', 'sms.019.source' => 'Endless',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.test'), ['test_phone' => '050-1234567'])
            ->assertSessionHas('status');
    }

    public function test_the_landing_content_is_editable(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'landing'), [
            'landing__title' => 'משפחה יקרה,',
            'landing__intro' => '<p>טקסט חדש</p>',
            'landing__features' => [
                ['icon' => 'dove', 'text' => 'תכונה ראשונה'],
                ['icon' => 'heart', 'text' => 'תכונה שנייה'],
            ],
            'landing__how_title' => 'איך זה עובד',
            'landing__how_intro' => 'הסבר',
            'landing__how_list_title' => 'דרך המערכת תוכלו:',
            'landing__how_list' => "שורה א\nשורה ב",
            'landing__note' => 'הערה',
            'landing__form_title' => 'אנחנו מחכים לכם',
            'landing__form_text' => 'טקסט',
            'landing__signature' => 'חתימה',
            'landing__signature_name' => 'צוות',
        ])->assertSessionHasNoErrors();

        $this->get('/shiryon')->assertOk()->assertSee('תכונה ראשונה')->assertSee('שורה א');
    }

    public function test_the_admin_can_work_as_an_owner_and_come_back(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $memorial = \App\Models\Memorial::factory()->for($owner)->create();

        $this->actingAs($admin)
            ->post(route('admin.memorials.login-as', $memorial))
            ->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($owner);

        $this->post(route('dashboard.stop-impersonating'))->assertRedirect(route('admin.memorials.index'));
        $this->assertAuthenticatedAs($admin);
    }
}
