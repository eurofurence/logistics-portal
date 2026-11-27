<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Settings\LoginSettings;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

class SettingsAppAuthentication extends AppAuthentication
{
    public function isEnabled(Authenticatable $user): bool
    {
        return app(LoginSettings::class)->two_factor_enabled && parent::isEnabled($user);
    }

    public function getManagementSchemaComponents(): array
    {
        return app(LoginSettings::class)->two_factor_enabled ? parent::getManagementSchemaComponents() : [];
    }

    public function getActions(): array
    {
        $actions = parent::getActions();

        foreach ($actions as $action) {
            if ($action->getName() === 'disableAppAuthentication') {
                $action->authorize(function (): bool {
                    $user = Filament::auth()->user();

                    return $user instanceof User && ! $user->requiresTwoFactorAuthentication();
                });
            }
        }

        return $actions;
    }
}
