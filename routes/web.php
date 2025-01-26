<?php

use Inertia\Inertia;
use App\Models\OrderRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

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

Route::redirect('/app/login', 'https://identity.eurofurence.org/')->name('login');

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
