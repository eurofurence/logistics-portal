<?php

namespace App\Filament\App\Resources\ClubMembers\Actions;

use App\Mail\ClubMemberMessage;
use App\Models\ClubMember;
use App\Services\ClubMemberFiles;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class SendClubMemberEmail
{
    public static function make(): Action
    {
        return Action::make('sendEmail')
            ->databaseTransaction(false)
            ->label(__('members.send_email'))->icon('heroicon-o-envelope')
            ->authorize('sendEmail')
            ->modalSubmitAction(fn (Action $action): Action => $action->icon('heroicon-o-paper-airplane'))
            ->modalCancelAction(fn (Action $action): Action => $action->icon('heroicon-o-x-mark'))
            ->visible(fn (ClubMember $record): bool => ! $record->trashed())
            ->fillForm(fn (ClubMember $record): array => ['recipient' => $record->email, 'reply_email' => Auth::user()->email, 'attachments' => [], 'uploads' => []])
            ->schema([
                TextInput::make('recipient')->label(__('members.recipient'))->email()->required()->maxLength(255),
                TextInput::make('reply_email')->label(__('members.reply_email'))->email()->required()->maxLength(255)->prefixIcon('heroicon-o-arrow-uturn-left'),
                TextInput::make('subject')->label(__('members.subject'))->required()->maxLength(255),
                Textarea::make('body')->label(__('members.message'))->required()->maxLength(10000),
                Select::make('attachments')->label(__('members.attachments'))->multiple()
                    ->helperText(__('members.email_attachment_hint'))
                    ->options(fn (ClubMember $record): array => $record->media()->where('collection_name', ClubMember::FILE_COLLECTION)->get()
                        ->mapWithKeys(fn ($file): array => [$file->id => ClubMemberFiles::originalName($file)])->all()),
                FileUpload::make('uploads')->label(__('members.uploads'))->multiple()->storeFiles(false)
                    ->disk(fn (): string => config('filesystems.default'))->visibility('private')
                    ->acceptedFileTypes(ClubMemberFiles::MIME_TYPES)->maxFiles(5)->maxSize(10000)
                    ->visible(fn (ClubMember $record): bool => Gate::allows('manageFiles', $record))
                    ->helperText(__('members.upload_hint')),
            ])
            ->action(function (array $data, ClubMember $record, Component $livewire, Action $action): void {
                Gate::authorize('sendEmail', $record->fresh());
                if (filled($livewire->mountedActions[$action->getNestingIndex()]['data']['uploads'] ?? [])) {
                    Gate::authorize('manageFiles', $record->fresh());
                }
                $service = app(ClubMemberFiles::class);
                try {
                    $files = $service->prepareEmail($record, $data['uploads'] ?? [], $data['attachments'] ?? []);
                } catch (ValidationException $exception) {
                    $errors = [];
                    foreach ($exception->errors() as $field => $messages) {
                        $errors['mountedActions.'.$action->getNestingIndex().'.data.'.$field] = $messages;
                    }
                    throw ValidationException::withMessages($errors);
                } catch (Throwable $exception) {
                    if ($exception instanceof AuthorizationException || $exception instanceof ModelNotFoundException) {
                        throw $exception;
                    }
                    report($exception);
                    Notification::make()->danger()->title(__('members.storage_failed'))->send();
                    $action->halt();
                }

                $data['uploads'] = [];
                $data['attachments'] = $files->pluck('id')->all();
                $livewire->getSchema('mountedActionSchema'.$action->getNestingIndex())->fill($data);
                $record->unsetRelation('media');

                try {
                    Mail::to($data['recipient'])->send(new ClubMemberMessage($data['subject'], $data['body'], $files, $data['reply_email']));
                } catch (Throwable $exception) {
                    report($exception);
                    Notification::make()->danger()->title(__('members.mail_failed'))->body(__('members.mail_failed_hint'))->send();
                    $action->halt();
                }

                Notification::make()->success()->title(__('members.mail_sent'))->send();
            });
    }
}
