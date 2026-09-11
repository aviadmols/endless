<?php

namespace App\Providers;

use App\Models\Memorial;
use App\Models\Memory;
use App\Models\User;
use App\Policies\MemorialPolicy;
use App\Policies\MemoryPolicy;
use App\Services\Mail\DynamicMailConfigurator;
use App\Services\Otp\OtpService;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\SmsManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(SmsManager::class);
        $this->app->singleton(OtpService::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('he');

        Gate::policy(Memorial::class, MemorialPolicy::class);
        Gate::policy(Memory::class, MemoryPolicy::class);
        Gate::define('admin', fn (User $user) => (bool) $user->is_admin);

        RateLimiter::for('otp', fn (Request $r) => Limit::perMinutes(10, 15)->by($r->ip()));
        RateLimiter::for('memories', fn (Request $r) => Limit::perHour(20)->by($r->ip()));
        RateLimiter::for('leads', fn (Request $r) => Limit::perHour(10)->by($r->ip()));

        // SMTP settings from the admin panel override the .env mailer at runtime.
        $this->app->booted(function () {
            $this->app->make(DynamicMailConfigurator::class)->apply();
        });

        View::composer('*', function ($view) {
            $view->with('siteSettings', $this->app->make(SettingsRepository::class));
        });
    }
}
