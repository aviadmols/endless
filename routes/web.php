<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MemorialController;
use App\Http\Controllers\MemoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shiryon', [HomeController::class, 'shiryon'])->name('shiryon');
Route::post('/leads', [HomeController::class, 'storeLead'])->middleware('throttle:leads')->name('leads.store');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:otp')->name('register.store');
    Route::get('/register/verify', [RegisterController::class, 'verifyForm'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:otp')->name('register.verify.store');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->middleware('throttle:otp')->name('register.resend');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login/send', [LoginController::class, 'sendCode'])->middleware('throttle:otp')->name('login.send');
    Route::get('/login/verify', [LoginController::class, 'verifyForm'])->name('login.verify');
    Route::post('/login/verify', [LoginController::class, 'verify'])->middleware('throttle:otp')->name('login.verify.store');
    Route::post('/login/resend', [LoginController::class, 'resend'])->middleware('throttle:otp')->name('login.resend');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('m/{memorial}')->scopeBindings()->group(function () {
    Route::get('/', [MemorialController::class, 'show'])->name('memorials.show');
    Route::get('/feed', [MemorialController::class, 'feed'])->name('memorials.feed');
    Route::get('/memory/{memory}', [MemoryController::class, 'show'])->name('memories.show');
    Route::get('/share/{token}', [MemoryController::class, 'create'])->name('memories.create');
    Route::post('/share/{token}', [MemoryController::class, 'store'])->middleware('throttle:memories')->name('memories.store');
});

/*
|--------------------------------------------------------------------------
| Personal area (memorial owner)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [Dashboard\OverviewController::class, 'index'])->name('index');

    Route::get('/memorial', [Dashboard\MemorialController::class, 'edit'])->name('memorial.edit');
    Route::put('/memorial', [Dashboard\MemorialController::class, 'update'])->name('memorial.update');
    Route::post('/memorial/media/{type}', [Dashboard\MediaController::class, 'store'])->name('memorial.media.store');
    Route::delete('/memorial/media/{type}', [Dashboard\MediaController::class, 'destroy'])->name('memorial.media.destroy');

    Route::post('/memorial/gallery', [Dashboard\GalleryController::class, 'store'])->name('gallery.store');
    Route::patch('/memorial/gallery/reorder', [Dashboard\GalleryController::class, 'reorder'])->name('gallery.reorder');
    Route::patch('/memorial/gallery/{image}', [Dashboard\GalleryController::class, 'update'])->name('gallery.update');
    Route::delete('/memorial/gallery/{image}', [Dashboard\GalleryController::class, 'destroy'])->name('gallery.destroy');

    Route::get('/memories', [Dashboard\MemoryController::class, 'index'])->name('memories.index');
    Route::get('/memories/create', [Dashboard\MemoryController::class, 'create'])->name('memories.create');
    Route::post('/memories', [Dashboard\MemoryController::class, 'store'])->name('memories.store');
    Route::get('/memories/{memory}/edit', [Dashboard\MemoryController::class, 'edit'])->name('memories.edit');
    Route::put('/memories/{memory}', [Dashboard\MemoryController::class, 'update'])->name('memories.update');
    Route::patch('/memories/{memory}/approve', [Dashboard\MemoryController::class, 'approve'])->name('memories.approve');
    Route::patch('/memories/{memory}/reject', [Dashboard\MemoryController::class, 'reject'])->name('memories.reject');
    Route::delete('/memories/{memory}', [Dashboard\MemoryController::class, 'destroy'])->name('memories.destroy');
    Route::delete('/memories/{memory}/images/{image}', [Dashboard\MemoryController::class, 'destroyImage'])->name('memories.images.destroy');

    Route::get('/book', [Dashboard\BookController::class, 'index'])->name('book');
    Route::get('/book/preview', [Dashboard\BookController::class, 'preview'])->name('book.preview');
    Route::put('/book', [Dashboard\BookController::class, 'update'])->name('book.update');

    Route::get('/share', [Dashboard\ShareController::class, 'index'])->name('share');
    Route::post('/share/regenerate', [Dashboard\ShareController::class, 'regenerate'])->name('share.regenerate');

    Route::get('/account', [Dashboard\AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [Dashboard\AccountController::class, 'update'])->name('account.update');

    Route::post('/stop-impersonating', [Admin\MemorialController::class, 'stopImpersonating'])->name('stop-impersonating');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('index');

    Route::get('/settings/{group}', [Admin\SettingsController::class, 'edit'])->whereIn('group', ['general', 'mail', 'sms', 'landing'])->name('settings.edit');
    Route::put('/settings/{group}', [Admin\SettingsController::class, 'update'])->whereIn('group', ['general', 'mail', 'sms', 'landing'])->name('settings.update');
    Route::post('/settings/mail/test', [Admin\SettingsController::class, 'testMail'])->name('settings.mail.test');
    Route::post('/settings/sms/test', [Admin\SettingsController::class, 'testSms'])->name('settings.sms.test');

    Route::get('/memorials', [Admin\MemorialController::class, 'index'])->name('memorials.index');
    Route::get('/memorials/{memorial}', [Admin\MemorialController::class, 'edit'])->name('memorials.edit');
    Route::put('/memorials/{memorial}', [Admin\MemorialController::class, 'update'])->name('memorials.update');
    Route::delete('/memorials/{memorial}', [Admin\MemorialController::class, 'destroy'])->name('memorials.destroy');
    Route::post('/memorials/{memorial}/login-as', [Admin\MemorialController::class, 'loginAs'])->name('memorials.login-as');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle-admin', [Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    Route::get('/leads', [Admin\LeadController::class, 'index'])->name('leads.index');
    Route::patch('/leads/{lead}/handled', [Admin\LeadController::class, 'toggleHandled'])->name('leads.handled');
});

/*
| Serves files from storage/app/public when the public/storage symlink cannot be created (e.g. Windows).
*/
Route::get('/storage/{path}', [MediaController::class, 'serve'])->where('path', '.*')->name('media.serve');
