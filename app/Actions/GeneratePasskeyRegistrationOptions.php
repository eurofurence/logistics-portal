<?php

namespace App\Actions;

use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Support\Serializer;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;

class GeneratePasskeyRegistrationOptions extends GeneratePasskeyRegisterOptionsAction
{
    public function execute(HasPasskeys $authenticatable, bool $asJson = true): string|PublicKeyCredentialCreationOptions
    {
        $options = parent::execute($authenticatable, asJson: false);
        $options->pubKeyCredParams = [PublicKeyCredentialParameters::createPk(-7), PublicKeyCredentialParameters::createPk(-257)];

        return $asJson ? Serializer::make()->toJson($options) : $options;
    }

    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            null,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
    }
}
