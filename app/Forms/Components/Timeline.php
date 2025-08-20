<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class Timeline extends Field
{
    protected string $view = 'forms.components.timeline';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dehydrated(false)
            ->columnSpanFull()
            ->disabled();
    }
}
