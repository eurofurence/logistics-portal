<?php

namespace App\Jobs;

use App\Services\ClubMemberPortal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendClubMemberResetLink implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $loginId) {}

    public function handle(ClubMemberPortal $portal): void
    {
        $portal->requestReset($this->loginId);
    }
}
