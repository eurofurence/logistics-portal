<?php

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

Route::redirect('/', 'https://identity.eurofurence.org/')->middleware('guest')->name('start');

Route::prefix('/auth')->name('auth.')->group(function () {
    Route::get('/callback', [\App\Http\Controllers\AuthController::class,'loginCallback'])->middleware('guest')->name('login.callback');
    Route::post('/logout', [\App\Http\Controllers\AuthController::class,'logout'])->middleware('auth')->name('logout');
    Route::get('/frontchannel-logout', [\App\Http\Controllers\AuthController::class,'logoutCallback'])->name('logout.callback');
});

Route::get('/app/oauth/identity', function () {
    return Socialite::driver('identity')->redirect();
});

Route::redirect('/app/artisan', '/app')->name('filament.app.pages.artisan');

Route::fallback(function () {
    return redirect(config('auth.auth_direct_url'));
});

Route::get('/login', function () {
    $prevUrl = url()->previous();

    if (! $prevUrl) {
        return redirect(config('auth.auth_direct_url'));
    }

    $path = parse_url($prevUrl, PHP_URL_PATH);

    $panelId = explode('/', trim($path, '/'))[0];

    if (! in_array($panelId, array_keys(Filament::getPanels()))) {
        return redirect(config('auth.auth_direct_url'));
    }

    return redirect(route("filament.{$panelId}.auth.login"));
})->name('filament.app.pages.manage-login');

Route::get('/theme', function () {
    $prevUrl = url()->previous();

    if (! $prevUrl) {
        return redirect(config('auth.auth_direct_url'));
    }

    $path = parse_url($prevUrl, PHP_URL_PATH);

    $panelId = explode('/', trim($path, '/'))[0];

    if (! in_array($panelId, array_keys(Filament::getPanels()))) {
        return redirect(config('auth.auth_direct_url'));
    }

    return redirect(route("filament.{$panelId}.auth.login"));
})->name('filament.app.pages.manage-theme');
