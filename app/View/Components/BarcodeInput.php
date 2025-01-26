<?php

namespace App\View\Components;

use Filament\Forms\Components\TextInput;

class BarcodeInput extends TextInput
{
    protected string $view = 'components.barcode-scanner'; // View namespaced correctly

    protected function setUp(): void
    {
        parent::setUp();

        // Set default properties for the BarcodeInput
        $this->label('Barcode Input')
            ->placeholder('Enter barcode...');
    }

    /**
     * Set a custom icon for the barcode input.
     *
     * @param  string  $icon  The SVG or HTML for the icon.
     */
    public function icon(string $icon): static
    {
        return $this->extraAttributes(['icon' => $icon]);
    }

    /**
     * Set a custom title for the scan modal.
     *
     * @param  string  $icon  The title for the scan modal
     */
    public function title(string $title): static
    {
        return $this->extraAttributes(['title' => $title]);
    }
}
