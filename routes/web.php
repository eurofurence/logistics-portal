<?php

use Illuminate\Support\Facades\Log;
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

if (config('auth.auth_ui') == false) {
    #APP
    Route::redirect('/app/login', config('auth.auth_direct_url'))->name('login');
    Route::redirect('/app/register', config('auth.auth_direct_url'))->name('register');
    Route::redirect('/app/password-reset/reset', config('auth.auth_direct_url'))->name('ResetPassword');
    Route::redirect('/app/password-reset/request', config('auth.auth_direct_url'))->name('RequestPasswordReset');

    #ADMIN
    Route::redirect('/admin/login', config('auth.auth_direct_url'))->name('login');
    Route::redirect('/admin/register', config('auth.auth_direct_url'))->name('register');
    Route::redirect('/admin/password-reset/reset', config('auth.auth_direct_url'))->name('ResetPassword');
    Route::redirect('/admin/password-reset/request', config('auth.auth_direct_url'))->name('RequestPasswordReset');
};

Route::prefix('/auth')->name('auth.')->group(function () {
    Route::get('/callback', [\App\Http\Controllers\AuthController::class,'loginCallback'])->middleware('guest')->name('login.callback');
    Route::post('/logout', [\App\Http\Controllers\AuthController::class,'logout'])->middleware('auth')->name('logout');
    Route::get('/frontchannel-logout', [\App\Http\Controllers\AuthController::class,'logoutCallback'])->name('logout.callback');
});

Route::redirect('/app/artisan', '/app')->name('filament.app.pages.artisan');

Route::fallback(function () {
    Log::warning('Route not found: ' . request()->path());
    abort(404);
});
