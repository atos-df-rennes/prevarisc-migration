<?php

namespace App\Service;

final readonly class Authentification
{
    public function __construct(private bool $casEnabled, private bool $ntlmEnabled, private bool $ldapEnabled)
    {
    }

    public function ssoEnabled(): bool
    {
        return $this->casEnabled || $this->ntlmEnabled || $this->ldapEnabled;
    }
}
