<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements \Filament\Auth\Http\Responses\Contracts\LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        if (config('app.identity_mode')) {
            return redirect()->away('https://identity.eurofurence.org');
        }

        return redirect()->to(Filament::getLoginUrl());
    }
}
