<?php

namespace App\Filament\App\Resources\OrderRequestResource\Pages;

use App\Filament\App\Resources\OrderRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderRequest extends CreateRecord
{
    protected static string $resource = OrderRequestResource::class;
}
