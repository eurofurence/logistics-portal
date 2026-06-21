<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ZipArchive;

class CreateZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $billIds,
        public User $user
    ) {}

    public function handle(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $bills = Bill::whereIn('id', $this->billIds)->get();
        $zipName = 'bills_' . now()->format('Y-m-d_H-i-s') . '_' . Str::random(8) . '.zip';

        // Use a dedicated path for public access via signed URL
        $storagePath = 'zips/' . $zipName;
        $tempLocalPath = tempnam(sys_get_temp_dir(), 'zip_');

        $zip = new ZipArchive();
        if ($zip->open($tempLocalPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($bills as $bill) {
                $departmentSlug = Str::slug($bill->connected_department?->name ?? 'no_department');
                $eventSlug = Str::slug($bill->connected_event?->name ?? 'no_event');
                $folderName = $departmentSlug . '/' . $eventSlug . '/' . $bill->id.'_'.Str::slug($bill->title).'_'.Str::random(8);
                $zip->addEmptyDir($folderName);

                // Add info.txt
                $infoContent = __('general.id').': ' . $bill->id . "\n";
                $infoContent .= __('general.title').': ' . $bill->title . "\n";
                $infoContent .= __('general.bill_amount').': ' . $bill->value . ' ' . $bill->currency . "\n";
                $infoContent .= __('general.status').': ' . $bill->status . "\n";
                $infoContent .= __('general.description').': ' . $bill->description . "\n";
                $infoContent .= __('general.comment').': ' . $bill->comment . "\n";
                $infoContent .= __('general.advance_payment').': ' . $bill->advance_payment_value . "\n";
                $infoContent .= __('general.advance_payment_to').': ' . $bill->advance_payment_receiver . "\n";
                $infoContent .= __('general.repayment_method').': ' . $bill->repayment_method . "\n";
                $infoContent .= __('general.exchange_rate').': ' . $bill->exchange_rate . "\n";
                $infoContent .= __('general.reimbursement_to_invoice_issuer').': ' . ($bill->reimbursement_to_invoice_issuer ? __('general.yes') : __('general.no')) . "\n";
                $infoContent .= __('general.added_by').': ' . ($bill->addedBy ? $bill->addedBy->name : 'N/A') . "\n";
                $infoContent .= __('general.edited_by').': ' . ($bill->editedBy ? $bill->editedBy->name : 'N/A') . "\n";
                $infoContent .= __('general.department').': ' . ($bill->connected_department ? $bill->connected_department->name : 'N/A') . "\n";
                $infoContent .= __('general.order_event').': ' . ($bill->connected_event ? $bill->connected_event->name : 'N/A') . "\n";
                $infoContent .= "\n--- " . __('timeline.status_history') . " ---\n";
                foreach ($bill->statusHistory() as $history) {
                    $infoContent .= __('general.date') . ": {$history->created_at} | " . __('general.user') . ": " . ($history->user ? $history->user->name : 'N/A') . ' | ';
                    if (isset($history->description['key'])) {
                        $params = $history->description['params'] ?? [];
                        if (isset($params['old'])) {
                            $params['old'] = __('general.' . $params['old'], [], 'de') !== 'general.' . $params['old'] ? __('general.' . $params['old']) : $params['old'];
                        }
                        if (isset($params['new'])) {
                            $params['new'] = __('general.' . $params['new'], [], 'de') !== 'general.' . $params['new'] ? __('general.' . $params['new']) : $params['new'];
                        }
                        $infoContent .= __($history->description['key'], $params);
                    } else {
                        $infoContent .= $history->title;
                    }
                    $infoContent .= "\n";
                }
                $infoContent = mb_convert_encoding($infoContent, 'UTF-8', 'UTF-8');
                $zip->addFromString($folderName.'/info.txt', "\xEF\xBB\xBF".$infoContent);

                foreach ($bill->getMedia('bills') as $media) {
                    $disk = Storage::disk($media->disk);
                    $path = $media->getPath();

                    if ($disk->exists($path)) {
                        $stream = $disk->readStream($path);
                        if (is_resource($stream)) {
                            $tmpFile = tempnam(sys_get_temp_dir(), 'media_');
                            file_put_contents($tmpFile, $stream);
                            $zip->addFile($tmpFile, $folderName.'/'.$media->file_name);
                        }
                    }
                }
            }
            $zip->close();
        }

        Storage::disk('local')->writeStream($storagePath, fopen($tempLocalPath, 'r'));
        unlink($tempLocalPath);

        $downloadUrl = URL::temporarySignedRoute(
            'bills.download-zip', now()->addHours(24), ['path' => $storagePath]
        );

        // Send email
        Notification::send($this->user, new GeneralNotification(
            $this->user->name,
            __('general.zip_download_ready', [], 'en'),
            __('general.zip_download_ready', [], 'en'),
            __('general.zip_download_ready_message', [], 'en'),
            null,
            null,
            null,
            $downloadUrl,
            __('general.download_zip', [], 'en')
        ));

        // Send database notification
        FilamentNotification::make()
            ->title(__('general.zip_download_ready'))
            ->body(__('general.zip_download_ready_message'))
            ->icon('heroicon-o-archive-box-arrow-down')
            ->iconColor('success')
            ->actions([
                Action::make(__('general.download_zip'))
                    ->url($downloadUrl)
                    ->button(),
            ])
            ->sendToDatabase($this->user);
    }
}
