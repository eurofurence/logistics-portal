<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderApprovalReminder;
use App\Services\ApplicationTime;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendOrderApprovalReminders extends Command
{
    protected $signature = 'orders:send-approval-reminders';

    protected $description = 'Send a daily digest of pending order approvals not previously reported to each approver';

    public function handle(): int
    {
        $lock = Cache::lock('orders:send-approval-reminders', 86400);

        if (! $lock->get()) {
            return self::SUCCESS;
        }

        $result = self::SUCCESS;

        try {
            foreach (User::where('locked', false)->lazyById(100) as $user) {
                try {
                    $this->sendReminder($user);
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error("Approval reminder failed for user {$user->id}.");
                    $result = self::FAILURE;
                }
            }
        } finally {
            $lock->release();
        }

        return $result;
    }

    private function sendReminder(User $user): void
    {
        $canApproveAllDepartments = $user->checkPermissionTo('can-approve-orders-for-other-departments');
        $departmentIds = $user->getDepartmentsWithPermission('can-approve-orders')->pluck('id');

        if (! $canApproveAllDepartments && $departmentIds->isEmpty()) {
            return;
        }

        $today = ApplicationTime::now()->startOfDay();

        if (DB::table('order_approval_reminders')
            ->where('user_id', $user->id)
            ->where('sent_at', '>=', $today->utc())
            ->where('sent_at', '<', $today->addDay()->utc())
            ->exists()) {
            return;
        }

        $orders = Order::query()
            ->with(['event', 'department'])
            ->where('status', 'awaiting_approval')
            ->when(! $canApproveAllDepartments, fn (EloquentBuilder $query): EloquentBuilder => $query->whereIn('department_id', $departmentIds))
            ->whereNotExists(function (Builder $query) use ($user): void {
                $query->selectRaw('1')
                    ->from('order_approval_reminders')
                    ->whereColumn('order_approval_reminders.order_id', 'orders.id')
                    ->where('order_approval_reminders.user_id', $user->id);
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (Order $order): bool => $order->canBeApprovedBy($user) && $user->can('view', $order))
            ->values();

        if ($orders->isEmpty()) {
            return;
        }

        Notification::sendNow($user, new OrderApprovalReminder($orders));

        $sentAt = now();

        DB::table('order_approval_reminders')->insert($orders->map(fn (Order $order): array => [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'sent_at' => $sentAt,
        ])->all());
    }
}
