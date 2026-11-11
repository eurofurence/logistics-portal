<?php

namespace App\Console\Commands;

use App\Services\OrderEventNotifications;
use Illuminate\Console\Command;

class SendOrderEventNotifications extends Command
{
    protected $signature = 'events:send-order-notifications';

    protected $description = 'Queue due event deadline reminders and order-opening announcements';

    public function handle(OrderEventNotifications $notifications): int
    {
        $notifications->schedule();

        return self::SUCCESS;
    }
}
