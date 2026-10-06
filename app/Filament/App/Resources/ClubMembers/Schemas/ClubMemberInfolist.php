<?php

namespace App\Filament\App\Resources\ClubMembers\Schemas;

use App\Models\ClubMember;
use App\Services\ClubMemberFiles;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class ClubMemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $entries = [];
        foreach (ClubMember::TEXT_FIELDS as $field) {
            $entries[] = TextEntry::make($field)->label(__('members.'.$field))->placeholder('—')->columnSpan($field === 'comment' ? 'full' : 1);
        }
        foreach (ClubMember::DATE_FIELDS as $field) {
            $entries[] = TextEntry::make($field)->label(__('members.'.$field))->date('d.m.Y')->placeholder('—');
        }
        $entries[] = TextEntry::make('status')->label(__('members.status'))->badge()
            ->state(fn (ClubMember $record): string => __('members.'.$record->membershipStatus()));

        return $schema->columns(1)->components([
            Tabs::make('member')->tabs([
                Tab::make(__('members.details'))->icon('heroicon-o-identification')->columns(['default' => 1, 'md' => 2])->schema($entries),
                Tab::make(__('members.files'))->icon('heroicon-o-paper-clip')->schema(fn (ClubMember $record): array => Gate::allows('view', $record)
                    ? $record->media()->where('collection_name', ClubMember::FILE_COLLECTION)->get()->map(fn ($media): TextEntry => TextEntry::make('file_'.$media->id)
                        ->hiddenLabel()->state(ClubMemberFiles::originalName($media))
                        ->url(route('club-members.files.download', ['member' => $record->id, 'media' => $media->id])))->all()
                    : []),
            ]),
        ]);
    }
}
