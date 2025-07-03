<?php

declare(strict_types=1);

use MartinPetricko\FilamentSentryFeedback\Enums\ColorScheme;

// config for MartinPetricko/FilamentSentryFeedback
return [
    /**
     * https://docs.sentry.io/concepts/key-terms/dsn-explainer/#where-to-find-your-data-source-name-dsn
     */
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /**
     * https://docs.sentry.io/platforms/javascript/user-feedback/configuration/
     */
    'widget' => [
        'element_id' => 'sentry-feedback',
        'color_scheme' => ColorScheme::Auto,
        'show_branding' => true,
        'show_name' => true,
        'is_name_required' => true,
        'show_email' => false,
        'is_email_required' => false,
        'enable_screenshot' => true,
    ],
];
