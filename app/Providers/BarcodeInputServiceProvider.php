<?php

namespace App\Providers;

use Filament\Forms\Components\Field;
use App\View\Components\BarcodeInput;
use Illuminate\Support\ServiceProvider;

class BarcodeInputServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the BarcodeInput component as a macro on the Field class
        Field::macro('barcodeInput', function ($name) {
            return BarcodeInput::make($name); // Use the 'make' method
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
