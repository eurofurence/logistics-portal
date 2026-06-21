<?php

use App\Http\Controllers\AuthController;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

Route::get('/bills/download-zip/{path}', function (string $path) {
    Log::info('BILL_DOWNLOAD: Route hit', ['path' => $path, 'ip' => request()->ip()]);

    // Check if link has already been used
    // We use the full URL (which includes the signature) as unique identifier
    $cacheKey = 'zip_download_used:'.sha1(request()->fullUrl());

    if (Cache::has($cacheKey)) {
        Log::warning('BILL_DOWNLOAD: Link already used', ['url' => request()->fullUrl(), 'ip' => request()->ip()]);
        abort(403, __('general.link_already_used'));
    }

    if (! Storage::disk('local')->exists($path)) {
        Log::error('BILL_DOWNLOAD: File not found', ['path' => $path, 'ip' => request()->ip()]);
        abort(404);
    }

    // Mark as used
    Cache::put($cacheKey, true, now()->addHours(12));

    Log::info('BILL_DOWNLOAD: Download started', ['path' => $path, 'ip' => request()->ip()]);

    return response()->streamDownload(function () use ($path) {
        $stream = Storage::disk('local')->readStream($path);
        while (! feof($stream)) {
            echo fread($stream, 8192);
            flush();
        }
        fclose($stream);
    }, basename($path), [
        'Content-Type' => 'application/zip',
        'Content-Length' => Storage::disk('local')->size($path),
    ]);
})->name('bills.download-zip')->middleware('signed')->where('path', '.*');

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
        Log::warning('BILL_DOWNLOAD: Fallback hit', ['url' => request()->fullUrl(), 'ip' => request()->ip()]);

        return redirect('/app/login');
    });
}

Route::redirect('/app/artisan', '/app')->name('filament.app.pages.artisan');
