<?php

namespace App\Filament\App\Resources\OrderEvents\Pages;

use App\Filament\App\Resources\OrderEvents\OrderEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderEvent extends CreateRecord
{
    protected static string $resource = OrderEventResource::class;
}
