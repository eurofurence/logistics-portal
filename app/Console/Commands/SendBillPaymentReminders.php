<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class SendBillPaymentReminders extends Command
{
    protected $signature = 'bills:send-payment-reminders';

    protected $description = 'Remind accountants about upcoming and overdue invoice payment deadlines';

    public function handle(): int
    {
        $lock = Cache::lock('bills:send-payment-reminders', 86400);

        if (! $lock->get()) {
            return self::SUCCESS;
        }

        try {
            $users = User::permission('get-bill-payment-reminder-accountant-notification')->get();

            if ($users->isEmpty()) {
                return self::SUCCESS;
            }

            $today = now('Europe/Berlin')->startOfDay();

            Bill::query()
                ->with('connected_department')
                ->whereNotIn('status', ['done', 'rejected'])
                ->whereNotNull('payment_deadline')
                ->whereDate('payment_deadline', '<=', $today->copy()->addDays(7)->toDateString())
                ->where(function (Builder $query) use ($today): void {
                    $query->where(function (Builder $query) use ($today): void {
                        $query->whereDate('payment_deadline', '>=', $today->toDateString())
                            ->whereNull('payment_reminder_sent_at');
                    })->orWhere(function (Builder $query) use ($today): void {
                        $query->whereDate('payment_deadline', '<', $today->toDateString())
                            ->whereNull('payment_overdue_reminder_sent_at');
                    });
                })
                ->chunkById(100, function (Collection $bills) use ($users, $today): void {
                    foreach ($bills as $bill) {
                        $overdue = $bill->payment_deadline->toDateString() < $today->toDateString();

                        foreach ($users as $user) {
                            $this->sendReminder($bill, $user, $overdue);
                        }

                        $sentColumn = $overdue ? 'payment_overdue_reminder_sent_at' : 'payment_reminder_sent_at';

                        Bill::query()
                            ->whereKey($bill->getKey())
                            ->whereDate('payment_deadline', $bill->payment_deadline->toDateString())
                            ->update([$sentColumn => now()]);
                    }
                });
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    private function sendReminder(Bill $bill, User $user, bool $overdue): void
    {
        $messageKey = $overdue ? 'general.bill_payment_overdue' : 'general.bill_payment_due_soon';
        $date = $bill->payment_deadline->format('d.m.Y');
        $link = route('filament.app.resources.bills.view', $bill);

        Notification::send($user, new GeneralNotification(
            username: $user->name,
            subject: __('general.bill_payment_reminder', [], 'en').' #'.$bill->id.' - '.$bill->title,
            titel: __('general.bill_payment_reminder', [], 'en'),
            message: __($messageKey, ['date' => $date], 'en'),
            details_title: $bill->title,
            details_message: __('general.department', [], 'en').': '.$bill->connected_department?->name,
            details_link: $link,
            details_link_title: __('general.show', [], 'en'),
        ));

        FilamentNotification::make()
            ->title(__('general.bill_payment_reminder'))
            ->body($bill->title.': '.__($messageKey, ['date' => $date]))
            ->icon('heroicon-o-clock')
            ->iconColor($overdue ? 'danger' : 'warning')
            ->actions([
                Action::make(__('general.mark_as_unread'))->markAsUnread(),
                Action::make(__('general.mark_as_read'))->markAsRead(),
                Action::make(__('general.show'))->url($link)->button(),
            ])
            ->sendToDatabase($user);
    }
}
