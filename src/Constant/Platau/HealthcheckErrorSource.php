<?php

namespace App\Constant\Platau;

/**
 * Source d'erreur lors du healthcheck Plat'AU.
 */
final class HealthcheckErrorSource
{
    /** Erreur interne Prevarisc : configuration, réseau, token PISTE indisponible. */
    public const PREVARISC = 0;

    /** Erreur côté Plat'AU : état dégradé, maintenance, code HTTP non-2xx. */
    public const PLATAU = 1;

    private function __construct()
    {
    }
}
