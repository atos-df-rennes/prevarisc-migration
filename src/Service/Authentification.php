<?php

namespace App\Service;

final class Authentification
{
    public function __construct(private readonly bool $casEnabled, private readonly bool $ntlmEnabled, private readonly bool $ldapEnabled)
    {
    }

    public function ssoEnabled(): bool
    {
        return $this->casEnabled || $this->ntlmEnabled || $this->ldapEnabled;
    }
}
