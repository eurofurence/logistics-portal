<?php

namespace App\Filament\App\Resources\ClubMembers\Schemas;

use App\Models\ClubMember;
use App\Services\ApplicationTime;
use App\Services\ClubMemberFiles;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class ClubMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        $fields = [];
        foreach (['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city'] as $field) {
            $fields[] = TextInput::make($field)->label(__('members.'.$field))->required()->maxLength(255);
        }
        $fields = [...$fields,
            Country::make('country')->label(__('members.country'))->required()->searchable(),
            TextInput::make('email')->label(__('members.email'))->email()->required()->maxLength(255),
            TextInput::make('phone')->label(__('members.phone'))->tel()->maxLength(50),
            DatePicker::make('birth_date')->label(__('members.birth_date'))->required()->maxDate(fn (): string => ApplicationTime::now()->toDateString()),
            DatePicker::make('joined_at')->label(__('members.joined_at'))->required()->afterOrEqual('birth_date'),
            DatePicker::make('left_at')->label(__('members.left_at'))->nullable()->afterOrEqual('joined_at'),
            Textarea::make('comment')->label(__('members.comment'))->maxLength(10000)->columnSpanFull(),
        ];

        return $schema->columns(1)->components([
            Tabs::make('member')->columnSpanFull()->tabs([
                Tab::make(__('members.details'))->icon('heroicon-o-identification')->columns(['default' => 1, 'md' => 2])->schema($fields),
                Tab::make(__('members.files'))->icon('heroicon-o-paper-clip')->schema([
                    TextEntry::make('file_hint')->hiddenLabel()->state(__('members.save_before_upload'))
                        ->visible(fn (?ClubMember $record): bool => ! $record?->exists),
                    SpatieMediaLibraryFileUpload::make('files')->label(__('members.files'))
                        ->collection(ClubMember::FILE_COLLECTION)
                        ->disk(fn (): string => config('filesystems.default'))->visibility('private')
                        ->multiple()->panelLayout('list')->maxFiles(5)->maxSize(10000)
                        ->acceptedFileTypes(ClubMemberFiles::MIME_TYPES)->previewable(false)->downloadable()
                        ->helperText(__('members.upload_hint'))
                        ->visible(fn (?ClubMember $record): bool => (bool) $record?->exists)
                        ->disabled(fn (?ClubMember $record): bool => ! $record || ! Gate::allows('manageFiles', $record) || $record->trashed())
                        ->getUploadedFileUsing(function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                            $record = $component->getRecord();
                            if (! $record || ! Gate::allows('view', $record)) {
                                return null;
                            }
                            $media = $record->media()->where('collection_name', ClubMember::FILE_COLLECTION)->where('uuid', $file)->first();
                            if (! $media) {
                                return null;
                            }

                            return [
                                'name' => $media->getCustomProperty('original_name', $media->file_name),
                                'size' => $media->size, 'type' => $media->mime_type,
                                'url' => route('club-members.files.download', ['member' => $record->id, 'media' => $media->id]),
                            ];
                        })
                        ->saveRelationshipsUsing(function (SpatieMediaLibraryFileUpload $component, ClubMember $record): void {
                            app(ClubMemberFiles::class)->sync($record, array_values($component->getRawState() ?? []));
                        }),
                ]),
            ]),
        ]);
    }
}
