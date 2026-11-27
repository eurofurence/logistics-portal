<?php

namespace App\Actions;

use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Support\Serializer;
use Webauthn\PublicKeyCredentialRequestOptions;

class GeneratePasskeyAuthenticationOptions extends GeneratePasskeyAuthenticationOptionsAction
{
    public function execute(): string
    {
        $options = Serializer::make()->fromJson(parent::execute(), PublicKeyCredentialRequestOptions::class);
        $options->userVerification = PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED;
        $json = Serializer::make()->toJson($options);
        Session::put('passkey-authentication-options', $json);

        return $json;
    }
}
