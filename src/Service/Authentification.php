<?php

namespace App\Service;

final class Authentification
{
    /** @var bool */
    private $casEnabled;

    /** @var bool */
    private $ntlmEnabled;

    /** @var bool */
    private $ldapEnabled;

    public function __construct(bool $casEnabled, bool $ntlmEnabled, bool $ldapEnabled)
    {
        $this->casEnabled = $casEnabled;
        $this->ntlmEnabled = $ntlmEnabled;
        $this->ldapEnabled = $ldapEnabled;
    }

    public function ssoEnabled(): bool
    {
        return $this->casEnabled || $this->ntlmEnabled || $this->ldapEnabled;
    }
}
