<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderArticle extends CreateRecord
{
    protected static string $resource = OrderArticleResource::class;
}
