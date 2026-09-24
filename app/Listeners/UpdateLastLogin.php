<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLogin
{
    public function handle(Login $event): void
    {
        $event->user->update(['last_login' => now()]);
    }
}
