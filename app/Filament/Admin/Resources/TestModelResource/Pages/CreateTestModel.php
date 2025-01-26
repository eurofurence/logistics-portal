<?php

namespace App\Filament\Admin\Resources\TestModelResource\Pages;

use Filament\Actions;
use RuntimeException;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use App\Filament\Admin\Resources\TestModelResource;

class CreateTestModel extends CreateRecord
{
    protected static string $resource = TestModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Test')
                ->label('Test Bugsnag')
                ->action(function () {
                    Bugsnag::notifyException(new RuntimeException("Test error"));
                }),
        ];
    }
}
