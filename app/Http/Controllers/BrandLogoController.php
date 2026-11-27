<?php

namespace App\Http\Controllers;

use App\Settings\ThemeSettings;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BrandLogoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ThemeSettings $settings): StreamedResponse
    {
        abort_if(blank($settings->logo), 404);
        $disk = Storage::disk();
        abort_unless($disk->exists($settings->logo), 404);

        return $disk->response($settings->logo, null, [
            'Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function memberLogin(ThemeSettings $settings): StreamedResponse|BinaryFileResponse
    {
        $disk = Storage::disk();
        $headers = ['Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff'];

        foreach ([$settings->member_login_logo, $settings->logo] as $path) {
            try {
                if ($path && $disk->exists($path)) {
                    return $disk->response($path, null, $headers);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->file(public_path('images/logo-round-filled.png'), $headers);
    }
}
