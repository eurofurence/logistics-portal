<?php

namespace App\Filament\App\Resources\BillResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\App\Resources\BillResource;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    public function getTitle(): string | Htmlable
    {
        return __('general.submit');
    }
}
