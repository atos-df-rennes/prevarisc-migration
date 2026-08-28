<?php

declare(strict_types=1);

namespace App\Security\Voter\Sdis29;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Garde-fou d'activation des portages SDIS29 (cf. app.sdis29_custom_enabled,
 * dérivé de PREVARISC_THIRDPARTY_PLUGINS). Utilisé via
 * `@IsGranted(Sdis29Voter::ENABLED)` sur les contrôleurs/routes spécifiques à
 * SDIS29, à la place d'une vérification manuelle dans chaque action : le même
 * artefact applicatif étant déployé pour tous les SDIS, ce voter évite
 * d'exposer ces fonctionnalités chez les autres clients.
 */
class Sdis29Voter implements VoterInterface
{
    public const ENABLED = 'sdis29_enabled';

    /** @var bool */
    private $sdis29CustomEnabled;

    public function __construct(bool $sdis29CustomEnabled)
    {
        $this->sdis29CustomEnabled = $sdis29CustomEnabled;
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!in_array(self::ENABLED, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->sdis29CustomEnabled ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}
