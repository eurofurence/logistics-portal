<?php

use App\Http\Controllers\AuthController;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
| For Login: /app/oauth/identity
*/

if (config('app.identity_mode')) {
    // Eurofurence Identity SSO Routen
    Route::redirect('/', 'https://identity.eurofurence.org/')->middleware('guest')->name('start');

    Route::prefix('/auth')->name('auth.')->group(function () {
        Route::get('/callback', [AuthController::class, 'loginCallback'])->middleware('guest')->name('login.callback');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
        Route::get('/frontchannel-logout', [AuthController::class, 'logoutCallback'])->name('logout.callback');
    });

    Route::get('/app/oauth/identity', function () {
        return Socialite::driver('identity')->redirect();
    });

    Route::fallback(function () {
        return redirect(config('auth.auth_direct_url'));
    });
} else {
    // Standard-Login Routen (z.B. Filament), wenn identity_mode inaktiv ist
    Route::redirect('/', '/app/login')->middleware('guest')->name('start');

    Route::fallback(function () {
        return redirect('/app/login');
    });
}

Route::redirect('/app/artisan', '/app')->name('filament.app.pages.artisan');

Route::get('/login', function () {
    $prevUrl = url()->previous();

    if (! $prevUrl) {
        return redirect(config('app.identity_mode') ? config('auth.auth_direct_url') : '/app/login');
    }

    $path = parse_url($prevUrl, PHP_URL_PATH);

    $panelId = explode('/', trim($path, '/'))[0];

    if (! in_array($panelId, array_keys(Filament::getPanels()))) {
        return redirect(config('app.identity_mode') ? config('auth.auth_direct_url') : '/app/login');
    }

    return redirect(route("filament.{$panelId}.auth.login"));
})->name('filament.app.pages.manage-login');

Route::get('/theme', function () {
    $prevUrl = url()->previous();

    if (! $prevUrl) {
        return redirect(config('app.identity_mode') ? config('auth.auth_direct_url') : '/app/login');
    }

    $path = parse_url($prevUrl, PHP_URL_PATH);

    $panelId = explode('/', trim($path, '/'))[0];

    if (! in_array($panelId, array_keys(Filament::getPanels()))) {
        return redirect(config('app.identity_mode') ? config('auth.auth_direct_url') : '/app/login');
    }

    return redirect(route("filament.{$panelId}.auth.login"));
})->name('filament.app.pages.manage-theme');
