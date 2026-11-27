<?php

namespace App\Filament\App\Resources\OrderEvents\Pages;

use App\Actions\LinkedRecordDeleteActions;
use App\Filament\App\Resources\OrderEvents\OrderEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Validation\ValidationException;

class EditOrderEvent extends EditRecord
{
    protected static string $resource = OrderEventResource::class;

    protected ?bool $hasUnsavedDataChangesAlert = true;

    #[Unexposed]
    public function assertNotificationsSaved(): void
    {
        $hash = md5((string) str(json_encode($this->data, JSON_UNESCAPED_UNICODE))->replace('\\\\', ''));
        if ($hash !== $this->savedDataHash) {
            throw ValidationException::withMessages(['data.name' => __('event-notifications.save_first')]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            LinkedRecordDeleteActions::single(DeleteAction::make())
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
        ];
    }
}
