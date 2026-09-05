<?php

namespace App\Filament\App\Resources\Bills\Tables;

use App\Jobs\CreateZipJob;
use App\Models\Bill;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters(), layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(self::getRecordActions())
            ->toolbarActions(self::getToolbarActions())
            ->groups(self::getGroups())
            ->defaultGroup('connected_department.name')
            ->deferLoading()
            ->searchDebounce('750ms')
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->toggleable(true, true)
                ->sortable()
                ->label(__('general.id')),
            TextColumn::make('title')
                ->label(__('general.title'))
                ->searchable()
                ->sortable(),
            TextColumn::make('connected_department.name')
                ->label(__('general.department'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('connected_event.name')
                ->label(__('general.order_event'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('status')
                ->badge()
                ->label(__('general.status'))
                ->sortable()
                ->toggleable()
                ->color(fn (string $state): string => match ($state) {
                    'done' => 'success',
                    'on_hold' => 'gray',
                    'checking' => 'checking',
                    'processing' => 'warning',
                    'open' => 'warning',
                    'rejected' => 'danger',
                })
                ->icon(fn (string $state): string => match ($state) {
                    'on_hold' => 'heroicon-o-clock',
                    'checking' => 'heroicon-o-arrow-path',
                    'processing' => 'heroicon-o-arrow-path',
                    'open' => 'heroicon-o-document-currency-dollar',
                    'ordered' => 'heroicon-o-shopping-cart',
                    'done' => 'heroicon-o-check',
                    'rejected' => 'heroicon-o-x-circle',
                })
                ->formatStateUsing(function ($state) {
                    return strtoupper(str_replace('_', ' ', $state));
                }),
            TextColumn::make('payment_deadline')
                ->label(__('general.bill_payment_deadline'))
                ->date('d.m.Y')
                ->placeholder('—')
                ->sortable()
                ->toggleable()
                ->badge()
                ->color(fn (Bill $record): string => self::isPaymentOverdue($record) ? 'danger' : 'gray')
                ->icon(fn (Bill $record): ?Heroicon => self::isPaymentOverdue($record) ? Heroicon::OutlinedExclamationTriangle : null)
                ->description(fn (Bill $record): ?string => self::isPaymentOverdue($record) ? __('general.bill_payment_overdue_label') : null),
            TextColumn::make('value')
                ->label(__('general.value'))
                ->formatStateUsing(function ($record) {
                    $priceFormatted = number_format($record->value, 2, ',', '.');

                    $symbol = match ($record->currency) {
                        'EUR' => '€',
                        'USD' => '$',
                        'GBP' => '£',
                        'JPY' => '¥',
                        'CHF' => 'CHF',
                        'CAD' => '$',
                        'AUD' => '$',
                        'NZD' => '$',
                        'CNY' => '¥',
                        'INR' => '₹',
                        'BRL' => 'R$',
                        'ZAR' => 'R',
                        'KRW' => '₩',
                        'MXN' => '$',
                        'SEK' => 'kr',
                        'NOK' => 'kr',
                        'DKK' => 'kr',
                        'PLN' => 'zł',
                        'TRY' => '₺',
                        'SGD' => '$',
                        'HKD' => '$',
                        'THB' => '฿',
                        'IDR' => 'Rp',
                        'MYR' => 'RM',
                        default => '€',
                    };

                    return $priceFormatted.' '.$symbol;
                })
                ->sortable()
                ->toggleable(),
            TextColumn::make('created_at')
                ->label(__('general.created_at'))
                ->sortable()
                ->date()
                ->toggleable(),
        ];
    }

    public static function getFilters(): array
    {
        return [
            Filter::make('payment_overdue')
                ->label(__('general.bill_payment_overdue_filter'))
                ->query(fn (Builder $query): Builder => $query
                    ->whereNotIn('status', ['done', 'rejected'])
                    ->whereDate('payment_deadline', '<', now('Europe/Berlin')->toDateString())),
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', Bill::class) || Gate::allows('forceDelete', Bill::class) || Gate::allows('bulkForceDelete', Bill::class) || Gate::allows('bulkRestore', Bill::class)),
            Filter::make('payment_deadline')
                ->schema([
                    DatePicker::make('payment_deadline_from')
                        ->label(__('general.bill_payment_deadline_from')),
                    DatePicker::make('payment_deadline_until')
                        ->label(__('general.bill_payment_deadline_until')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['payment_deadline_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('payment_deadline', '>=', $date),
                        )
                        ->when(
                            $data['payment_deadline_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('payment_deadline', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if ($data['payment_deadline_from'] ?? null) {
                        $indicators['payment_deadline_from'] = __('general.bill_payment_deadline_from').' '.Carbon::parse($data['payment_deadline_from'])->format('d.m.Y');
                    }

                    if ($data['payment_deadline_until'] ?? null) {
                        $indicators['payment_deadline_until'] = __('general.bill_payment_deadline_until').' '.Carbon::parse($data['payment_deadline_until'])->format('d.m.Y');
                    }

                    return $indicators;
                }),
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('created_from')
                        ->label(__('general.created_from'))
                        ->placeholder(fn ($state): string => 'Dec 18, '.now()->subYear()->format('Y')),
                    DatePicker::make('created_until')
                        ->label(__('general.created_until'))
                        ->placeholder(fn ($state): string => now()->format('M d, Y')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['created_from'] ?? null) {
                        $indicators['created_from'] = __('general.created_from').' '.Carbon::parse($data['created_from'])->toFormattedDateString();
                    }
                    if ($data['created_until'] ?? null) {
                        $indicators['created_until'] = __('general.created_until').' '.Carbon::parse($data['created_until'])->toFormattedDateString();
                    }

                    return $indicators;
                }),
            SelectFilter::make('order_event_id')
                ->label(__('general.order_event'))
                ->options(OrderEvent::all(['id', 'name'])->pluck('name', 'id'))
                ->default(function () {
                    $activeOrderEvent = OrderEvent::where('is_active', true)->first();

                    return $activeOrderEvent ? $activeOrderEvent->id : null;
                }),
            SelectFilter::make('department_id')
                ->multiple()
                ->label(__('general.department'))
                ->options(function (): array {
                    if (Auth::user()->can('can-see-all-bills')) {
                        return Department::query()->pluck('name', 'id')->toArray();
                    } else {
                        return Auth::user()->getDepartmentsWithPermission('view-Bill')->pluck('name', 'department_id')->toArray();
                    }
                }),
            SelectFilter::make('status')
                ->multiple()
                ->label(__('general.status'))
                ->options([
                    'done' => __('general.done'),
                    'on_hold' => __('general.on_hold'),
                    'checking' => __('general.checking'),
                    'processing' => __('general.processing'),
                    'open' => __('general.open'),
                    'rejected' => __('general.rejected'),
                ]),
            SelectFilter::make('added_by')
                ->multiple()
                ->label(__('general.added_by'))
                ->options(function (): array {
                    return User::query()->pluck('name', 'id')->toArray();
                }),
        ];
    }

    private static function isPaymentOverdue(Bill $record): bool
    {
        return $record->payment_deadline !== null
            && ! in_array($record->status, ['done', 'rejected'], true)
            && $record->payment_deadline->toDateString() < now('Europe/Berlin')->toDateString();
    }

    public static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                ActionGroup::make([
                    ReplicateAction::make()
                        ->excludeAttributes(['status'])
                        ->schema([
                            TextEntry::make('duplicate_hint')
                                ->label(__('general.hint'))
                                ->state(__('general.duplicate_note_1')),
                            TextInput::make('title')
                                ->label(__('general.title'))
                                ->required()
                                ->maxLength(64)
                                ->unique(),
                        ])
                        ->beforeReplicaSaved(function (Model $replica, array $data): void {
                            $replica->fill($data);
                            $replica->status = 'open';
                        })
                        ->successRedirectUrl(fn (Model $replica): string => route('filament.app.resources.bills.edit', $replica))
                        ->successNotificationTitle(__('general.entry_duplicated')),
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalHeading(function ($record): string {
                            return __('general.delete').': '.$record->title;
                        }),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                    ViewAction::make(),
                ])->dropdown(false),
                ActionGroup::make([
                    Action::make('set_status')
                        ->label(__('general.set_status'))
                        ->action(function (Model $record, array $data): void {
                            $record->update(['status' => $data['status']]);
                        })
                        ->icon('heroicon-o-ellipsis-horizontal-circle')
                        ->schema([
                            Select::make('status')
                                ->label(__('general.status'))
                                ->options([
                                    'done' => __('general.done'),
                                    'on_hold' => __('general.on_hold'),
                                    'checking' => __('general.checking'),
                                    'processing' => __('general.processing'),
                                    'open' => __('general.open'),
                                    'rejected' => __('general.rejected'),
                                ])
                                ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                ->required(),
                        ])
                        ->visible(fn () => Auth::user()->can('can-change-bill-status')),
                ])->dropdown(false),
            ]),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('download_zip')
                    ->label(__('general.download_zip'))
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->requiresConfirmation()
                    ->modalHeading(__('general.download_zip_confirmation'))
                    ->modalDescription(__('general.download_zip_explanation'))
                    ->action(function (Collection $records) {
                        if ($records->count() > 25) {
                            if (config('queue.default') === 'sync') {
                                Notification::make()
                                    ->title(__('general.async_processing_disabled'))
                                    ->body(__('general.async_processing_disabled_message'))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            CreateZipJob::dispatch($records->pluck('id')->toArray(), auth()->user());
                            Notification::make()
                                ->title(__('general.zip_process_started'))
                                ->body(__('general.zip_process_started_message'))
                                ->success()
                                ->send();

                            return;
                        }

                        set_time_limit(0);
                        $zipFile = tempnam(sys_get_temp_dir(), 'bills_zip');
                        $zip = new ZipArchive;
                        $zip->open($zipFile, ZipArchive::OVERWRITE);

                        $tempFiles = [];
                        foreach ($records as $bill) {
                            $departmentSlug = Str::slug($bill->connected_department?->name ?? 'no_department');
                            $eventSlug = Str::slug($bill->connected_event?->name ?? 'no_event');
                            $zip->addEmptyDir($departmentSlug);
                            $zip->addEmptyDir($departmentSlug.'/'.$eventSlug);
                            $folderName = $departmentSlug.'/'.$eventSlug.'/'.$bill->id.'_'.Str::slug($bill->title).'_'.Str::random(8);
                            $zip->addEmptyDir($folderName);

                            // Add info.txt
                            $infoContent = __('general.id').': '.$bill->id."\n";
                            $infoContent .= __('general.title').': '.$bill->title."\n";
                            $infoContent .= __('general.bill_amount').': '.$bill->value.' '.$bill->currency."\n";
                            $infoContent .= __('general.status').': '.$bill->status."\n";
                            $infoContent .= __('general.description').': '.$bill->description."\n";
                            $infoContent .= __('general.comment').': '.$bill->comment."\n";
                            $infoContent .= __('general.advance_payment').': '.$bill->advance_payment_value."\n";
                            $infoContent .= __('general.advance_payment_to').': '.$bill->advance_payment_receiver."\n";
                            $infoContent .= __('general.repayment_method').': '.$bill->repayment_method."\n";
                            $infoContent .= __('general.exchange_rate').': '.$bill->exchange_rate."\n";
                            $infoContent .= __('general.reimbursement_to_invoice_issuer').': '.($bill->reimbursement_to_invoice_issuer ? __('general.yes') : __('general.no'))."\n";
                            $infoContent .= __('general.added_by').': '.($bill->addedBy ? $bill->addedBy->name : 'N/A')."\n";
                            $infoContent .= __('general.edited_by').': '.($bill->editedBy ? $bill->editedBy->name : 'N/A')."\n";
                            $infoContent .= __('general.department').': '.($bill->connected_department ? $bill->connected_department->name : 'N/A')."\n";
                            $infoContent .= __('general.order_event').': '.($bill->connected_event ? $bill->connected_event->name : 'N/A')."\n";
                            $infoContent .= "\n--- ".__('timeline.status_history')." ---\n";
                            foreach ($bill->statusHistory() as $history) {
                                $infoContent .= __('general.date').": {$history->created_at} | ".__('general.user').': '.($history->user ? $history->user->name : 'N/A').' | ';
                                if (isset($history->description['key'])) {
                                    $params = $history->description['params'] ?? [];
                                    if (isset($params['old'])) {
                                        $params['old'] = __('general.'.$params['old'], [], 'de') !== 'general.'.$params['old'] ? __('general.'.$params['old']) : $params['old'];
                                    }
                                    if (isset($params['new'])) {
                                        $params['new'] = __('general.'.$params['new'], [], 'de') !== 'general.'.$params['new'] ? __('general.'.$params['new']) : $params['new'];
                                    }
                                    $infoContent .= __($history->description['key'], $params);
                                } else {
                                    $infoContent .= $history->title;
                                }
                                $infoContent .= "\n";
                            }
                            $infoContent = mb_convert_encoding($infoContent, 'UTF-8', 'UTF-8');
                            $zip->addFromString($folderName.'/info.txt', "\xEF\xBB\xBF".$infoContent);
                            // Add media
                            foreach ($bill->getMedia('bills') as $media) {
                                $tempPath = tempnam(sys_get_temp_dir(), 'media_');
                                $tempFiles[] = $tempPath;

                                $disk = Storage::disk($media->disk);

                                $path = $media->getPath();

                                if (! $disk->exists($path)) {
                                    Log::error("Media file not found on disk {$media->disk}: {$path}");

                                    continue;
                                }

                                $stream = $disk->readStream($path);
                                if (is_resource($stream)) {
                                    file_put_contents($tempPath, $stream);
                                } else {
                                    Log::error("Could not create stream for: {$path}");

                                    continue;
                                }

                                $zip->addFile($tempPath, $folderName.'/'.$media->file_name);
                            }
                        }

                        $zip->close();

                        // Cleanup temp files
                        foreach ($tempFiles as $file) {
                            if (file_exists($file)) {
                                unlink($file);
                            }
                        }

                        return response()->streamDownload(function () use ($zipFile) {
                            readfile($zipFile);
                            unlink($zipFile);
                        }, 'bills_'.now()->format('Y-m-d_H-i-s').'.zip');
                    })
                    ->visible(fn (): bool => Gate::allows('downloadZip', Bill::class))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()
                    ->visible(fn (): bool => Gate::allows('bulkDelete', Bill::class)),
                RestoreBulkAction::make()
                    ->visible(fn (): bool => Gate::allows('bulkRestore', Bill::class)),
            ]),
        ];
    }

    public static function getGroups(): array
    {
        return [
            Group::make('connected_event.name')
                ->label(__('general.order_event'))
                ->collapsible(),
            Group::make('created_at')
                ->label(__('general.date'))
                ->date()
                ->collapsible(),
            Group::make('status')
                ->label(__('general.status'))
                ->collapsible(),
            Group::make('connected_department.name')
                ->label(__('general.department'))
                ->collapsible(),
        ];
    }
}
