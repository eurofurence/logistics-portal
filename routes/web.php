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
    Log::info('BILL_DOWNLOAD: Route hit', [
        'path' => $path,
        'ip' => request()->ip(),
        'range' => request()->header('Range'),
    ]);

    // Check if link has already been used
    $cacheKey = 'zip_download_used:'.sha1(request()->fullUrl());
    $accessData = Cache::get($cacheKey);

    if ($accessData) {
        // Subsequent request: ONLY allow if it's a range request (for retries/browser behavior)
        if (! request()->header('Range') || $accessData['ip'] !== request()->ip() || now()->diffInMinutes($accessData['time']) > 15) {
            Log::warning('BILL_DOWNLOAD: Link already used or invalid retry', [
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
                'range' => request()->header('Range'),
            ]);
            abort(403, __('general.link_already_used'));
        }
    } else {
        // Mark as first used
        Cache::put($cacheKey, [
            'ip' => request()->ip(),
            'time' => now(),
        ], now()->addHours(12));
    }

    if (! Storage::disk('local')->exists($path)) {
        Log::error('BILL_DOWNLOAD: File not found', ['path' => $path, 'ip' => request()->ip()]);
        abort(404);
    }

    $fullPath = storage_path('app/'.$path);
    Log::info('BILL_DOWNLOAD: Streaming file', ['path' => $fullPath, 'ip' => request()->ip()]);

    return response()->file($fullPath, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="'.basename($path).'"',
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
Route::redirect('/app/manage-login', '/app')->name('filament.app.pages.manage-login');
Route::redirect('/app/manage-theme', '/app')->name('filament.app.pages.manage-theme');
