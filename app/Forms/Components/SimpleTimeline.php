<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class SimpleTimeline extends Field
{
    protected string $view = 'forms.components.timeline_simple';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dehydrated(false)
            ->columnSpanFull()
            ->disabled();
    }
}
