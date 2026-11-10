<?php

namespace App\Http\Controllers;

use App\Settings\ThemeSettings;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
}
