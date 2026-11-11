<?php

namespace App\Filament\App\Resources\OrderEvents\Schemas;

use App\Actions\OrderEventNotificationActions;
use App\Models\OrderEvent;
use App\Services\ApplicationTime;
use App\Services\OrderEventNotifications;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OrderEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('event')->columnSpanFull()->tabs([
                Tab::make(__('event-notifications.general'))->schema([
                    Section::make(__('general.informations'))->schema([
                        TextInput::make('name')->required()->label(__('general.name'))->unique(ignoreRecord: true),
                        DateTimePicker::make('order_deadline')->label(__('general.order_deadline'))->nullable()
                            ->timezone(ApplicationTime::timezone())->hint(ApplicationTime::timezone())->seconds(false)->live(),
                    ]),
                    Section::make(__('general.options'))->schema([
                        Toggle::make('locked')->label(__('general.locked'))->inline()->default(false),
                        Toggle::make('is_active')->inline()->default(false)->helperText(__('general.is_active_description')),
                    ]),
                ]),
                Tab::make(__('event-notifications.tab'))->schema([
                    TextEntry::make('notification_help')->hiddenLabel()->state(__('event-notifications.help'))->columnSpanFull(),
                    self::notificationSection('deadline'),
                    self::notificationSection('opening'),
                ]),
            ]),
        ]);
    }

    protected static function notificationSection(string $type): Section
    {
        $prefix = $type === 'deadline' ? 'deadline_reminder' : 'opening_notification';

        return Section::make(__('event-notifications.'.$type))->schema([
            Toggle::make($prefix.'_enabled')->label(__('event-notifications.enabled'))
                ->default($type === 'deadline')->live(),
            Select::make($prefix.'_mode')->label(__('event-notifications.mode'))
                ->options($type === 'deadline'
                    ? ['relative' => __('event-notifications.relative'), 'fixed' => __('event-notifications.fixed')]
                    : ['manual' => __('event-notifications.manual'), 'scheduled' => __('event-notifications.scheduled')])
                ->default($type === 'deadline' ? 'relative' : 'manual')->required()->live(),
            ...($type === 'deadline' ? [TextInput::make('deadline_reminder_days')->label(__('event-notifications.days'))
                ->numeric()->integer()->minValue(1)->maxValue(365)->default(7)->required()
                ->visible(fn (Get $get): bool => $get($prefix.'_mode') === 'relative')] : []),
            DateTimePicker::make($prefix.'_at')->label(__('event-notifications.at'))
                ->timezone(ApplicationTime::timezone())->hint(ApplicationTime::timezone())->seconds(false)
                ->visible(fn (Get $get): bool => $get($prefix.'_mode') === ($type === 'deadline' ? 'fixed' : 'scheduled'))
                ->required(fn (Get $get): bool => (bool) $get($prefix.'_enabled'))
                ->rules([fn (Get $get, ?OrderEvent $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record, $prefix): void {
                    if (blank($value)) {
                        return;
                    }
                    $date = ApplicationTime::toUtc($value);
                    $saved = $record?->{$prefix.'_at'};
                    if ((! $saved || ! $date->equalTo($saved)) && ! $date->isFuture()) {
                        $fail(__('event-notifications.future_required'));
                    }
                    if ($get('order_deadline') && $date->gte(ApplicationTime::toUtc($get('order_deadline')))) {
                        $fail(__('event-notifications.before_deadline'));
                    }
                }]),
            CheckboxList::make($prefix.'_channels')->label(__('event-notifications.channels'))
                ->options(['mail' => __('event-notifications.mail'), 'database' => __('event-notifications.database')])
                ->default(['mail', 'database'])->required()->minItems(1)->columns(2),
            TextEntry::make($prefix.'_status')->label(__('event-notifications.status'))
                ->state(fn (?OrderEvent $record): string => $record
                    ? app(OrderEventNotifications::class)->status($record, $type) : __('event-notifications.save_first')),
            Actions::make([
                OrderEventNotificationActions::send($type),
                OrderEventNotificationActions::send($type, true),
            ])->key($prefix.'_actions'),
        ]);
    }
}
