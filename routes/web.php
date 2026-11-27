<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandLogoController;
use App\Http\Controllers\ClubMemberFileController;
use App\Http\Controllers\ClubMemberPortalController;
use App\Http\Controllers\OrderArticleImageController;
use App\Http\Controllers\PasskeyLoginController;
use App\Http\Controllers\PasskeyOptionsController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PwaManifestController;
use App\Http\Middleware\CheckWhitelist;
use App\Http\Middleware\EnsureClubMemberAccess;
use App\Http\Middleware\UserIsLocked;
use App\Models\ClubMemberToken;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/pwa/manifest.json', PwaManifestController::class)->name('pwa.manifest');
Route::get('/branding/logo', BrandLogoController::class)->name('branding.logo');
Route::get('/branding/member-login-logo', [BrandLogoController::class, 'memberLogin'])
    ->withoutMiddleware([UserIsLocked::class, CheckWhitelist::class])->name('branding.member-login-logo');

Route::prefix('members')->name('member-portal.')
    ->withoutMiddleware([UserIsLocked::class, CheckWhitelist::class])
    ->controller(ClubMemberPortalController::class)->group(function (): void {
        Route::redirect('/', '/members/profile')->name('start');
        Route::post('locale', 'updateLocale')->name('locale.update');
        Route::get('login', 'showLogin')->name('login');
        Route::post('login', 'login')->middleware('throttle:member-portal-login')->name('authenticate');
        Route::get('forgot-password', 'showForgotPassword')->name('forgot-password');
        Route::post('forgot-password', 'forgotPassword')->middleware('throttle:member-portal-reset')->name('request-reset');

        foreach ([
            ClubMemberToken::ACTIVATION => 'activate',
            ClubMemberToken::RESET => 'reset-password',
            ClubMemberToken::EMAIL_CHANGE => 'confirm-email',
        ] as $purpose => $path) {
            Route::get($path, 'showTokenForm')->defaults('purpose', $purpose)->name($purpose);
            Route::post($path, 'redeemToken')->defaults('purpose', $purpose)->name($purpose.'.redeem')->middleware('throttle:member-portal-token');
        }

        Route::middleware(EnsureClubMemberAccess::class)->group(function (): void {
            Route::get('profile', 'showProfile')->name('profile');
            Route::patch('profile', 'updateProfile')->name('profile.update');
            Route::patch('profile/email', 'updateEmail')->middleware('throttle:member-portal-security')->name('email.update');
            Route::patch('profile/password', 'updatePassword')->middleware('throttle:member-portal-security')->name('password.update');
            Route::post('logout', 'logout')->name('logout');
        });
    });

foreach ([
    'mitglieder' => 'start',
    'mitglieder/login' => 'login',
    'mitglieder/profil' => 'profile',
    'mitglieder/aktivieren' => ClubMemberToken::ACTIVATION,
    'mitglieder/passwort-vergessen' => 'forgot-password',
    'mitglieder/passwort-zuruecksetzen' => ClubMemberToken::RESET,
    'mitglieder/email-bestaetigen' => ClubMemberToken::EMAIL_CHANGE,
] as $path => $routeName) {
    Route::get($path, fn (): RedirectResponse => to_route('member-portal.'.$routeName))
        ->withoutMiddleware([UserIsLocked::class, CheckWhitelist::class]);
}

Route::middleware(['auth', UserIsLocked::class, CheckWhitelist::class, 'throttle:30,1'])
    ->prefix('push-subscriptions')->name('push-subscriptions.')->group(function (): void {
        Route::post('/', [PushSubscriptionController::class, 'store'])->name('store');
        Route::delete('/', [PushSubscriptionController::class, 'destroy'])->name('destroy');
    });

Route::prefix('passkeys')->middleware(['guest', 'throttle:10,1'])->group(function (): void {
    Route::get('authentication-options', PasskeyOptionsController::class)->name('passkeys.authentication_options');
    Route::post('authenticate', PasskeyLoginController::class)->name('passkeys.login');
});

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

Route::get('/club-members/{member}/files/{media}', ClubMemberFileController::class)
    ->middleware('auth')->whereNumber('member')->whereNumber('media')->name('club-members.files.download');

Route::get('/order-articles/{article}/image/{conversion?}', [OrderArticleImageController::class, 'current'])
    ->middleware('auth')->whereNumber('article')
    ->where('conversion', 'original|thumbnail')->name('order-articles.current-image');

Route::get('/order-articles/{article}/images/{media}/{conversion?}', OrderArticleImageController::class)
    ->middleware('auth')->whereNumber('article')->whereNumber('media')
    ->where('conversion', 'original|thumbnail')->name('order-articles.images');

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
        // Log::warning('BILL_DOWNLOAD: Fallback hit', ['url' => request()->fullUrl(), 'ip' => request()->ip()]);

        return redirect('/app/login');
    });
}

Route::redirect('/app/artisan', '/app')->name('filament.app.pages.artisan');
Route::redirect('/app/manage-login', '/app')->name('filament.app.pages.manage-login');
Route::redirect('/app/manage-theme', '/app')->name('filament.app.pages.manage-theme');
Route::redirect('/app/manage-general', '/app')->name('filament.app.pages.manage-general');
