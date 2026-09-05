<?php

namespace App\Filament\App\Resources\OrderRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Grouping\Group;

class OrderRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('general.informations'))
                    ->schema([
                        \Filament\Schemas\Components\Group::make([
                            TextEntry::make('title')
                                ->label(__('general.title')),
                        ]),
                        TextEntry::make('message')
                            ->label(__('general.message')),
                        TextEntry::make('quantity')
                            ->label(__('general.quantity')),
                        TextEntry::make('url')
                            ->label(__('general.url'))
                            ->url(fn ($record) => $record->url, true)
                            ->default(__('general.not_set'))
                            ->limit(100)
                            ->visible(function ($record) {
                                return $record->url;
                            }),
                    ]),
                Section::make(__('general.moderation'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('general.status'))
                            ->badge()
                            ->icon(fn (string $state): string => match ($state) {
                                '0' => 'heroicon-o-clock',
                                '1' => 'heroicon-o-check-circle',
                                '2' => 'heroicon-o-arrow-path',
                                '3' => 'heroicon-o-bookmark',
                                '4' => 'heroicon-o-arrow-path',
                                '5' => 'heroicon-o-no-symbol',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                '0' => 'warning',
                                '1' => 'success',
                                '2' => 'warning',
                                '3' => 'info',
                                '4' => 'checking',
                                '5' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                '0' => __('general.open'),
                                '1' => __('general.finished'),
                                '2' => __('general.processing'),
                                '3' => __('general.note'),
                                '4' => __('general.checking'),
                                '5' => __('general.rejected'),
                                default => 'Unknown Status',
                            }),
                        TextEntry::make('comment')
                            ->default(__('general.not_set'))
                            ->label(__('general.comment')),
                    ])
                    ->visible(true),
                Section::make(__('general.other_infos'))
                    ->schema([
                        Flex::make([
                            \Filament\Schemas\Components\Group::make([
                                TextEntry::make('addedBy.name')
                                    ->label(__('general.added_by'))
                                    ->suffix(function ($record): ?string {
                                        $roles = $record->addedBy->getRolesInDepartment($record->department_id);

                                        if (! empty($roles)) {
                                            $roleNames = array_map(function ($role) {
                                                return $role['name'];
                                            }, $roles);

                                            return ' ('.__('general.currently').': '.implode(', ', $roleNames).')';
                                        }

                                        return null;
                                    }),
                                TextEntry::make('editedBy.name')
                                    ->label(__('general.edited_by')),
                            ]),
                            \Filament\Schemas\Components\Group::make([
                                TextEntry::make('created_at')
                                    ->label(__('general.created_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                                TextEntry::make('updated_at')
                                    ->label(__('general.updated_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                            ]),
                            \Filament\Schemas\Components\Group::make([
                                TextEntry::make('department.name')
                                    ->label(__('general.department')),
                                TextEntry::make('event.name')
                                    ->label(__('general.order_event')),
                            ]),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
