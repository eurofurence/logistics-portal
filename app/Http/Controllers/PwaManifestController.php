<?php

namespace App\Http\Controllers;

use App\Services\PwaConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaManifestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PwaConfiguration $configuration): JsonResponse
    {
        $locale = $request->query('lang', app()->getLocale());
        $locale = is_string($locale) && array_key_exists($locale, config('app.available_locales')) ? $locale : config('app.fallback_locale');

        return response()->json([
            'id' => '/app/',
            'name' => $configuration->name(),
            'short_name' => $configuration->name(),
            'description' => trans('pwa.description', [], $locale),
            'lang' => $locale,
            'start_url' => '/app/start',
            'scope' => '/app/',
            'display' => 'standalone',
            'background_color' => '#f9fafb',
            'theme_color' => '#007bff',
            'icons' => [
                ['src' => '/images/pwa-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/images/pwa-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
