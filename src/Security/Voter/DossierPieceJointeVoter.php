<?php

namespace App\Security\Voter;

use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Droits de modification et de suppression d'une pièce jointe de dossier (RG-PJ-009, RG-PJ-010, RG-PJ-023).
 *
 * @extends Voter<string, DossierPieceJointe>
 */
class DossierPieceJointeVoter extends Voter implements ResetInterface
{
    public const string MODIFICATION = 'edit_dossier_pj';

    public const string SUPPRESSION = 'delete_dossier_pj';

    /**
     * Décision edit_doss mémorisée par jeton et par dossier : la liste des pièces jointes
     * interroge ce voter deux fois par ligne et le vote edit_doss déclenche plusieurs requêtes.
     *
     * @var \WeakMap<TokenInterface, \WeakMap<Dossier, bool>>
     */
    private \WeakMap $editDossierDecisions;

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->editDossierDecisions = new \WeakMap();
    }

    public function reset(): void
    {
        $this->editDossierDecisions = new \WeakMap();
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MODIFICATION, self::SUPPRESSION], true)
            && $subject instanceof DossierPieceJointe;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $dossier = $subject->getDossier();
        $pieceJointe = $subject->getPieceJointe();

        if (null === $dossier || null === $pieceJointe) {
            return false;
        }

        if (true === $pieceJointe->getStatutEnum()?->bloqueModification()) {
            $vote?->addReason("Le statut Plat'AU de la pièce jointe interdit cette action.");

            return false;
        }

        return $this->canEditDossier($token, $dossier);
    }

    private function canEditDossier(TokenInterface $token, Dossier $dossier): bool
    {
        if (!isset($this->editDossierDecisions[$token])) {
            /** @var \WeakMap<Dossier, bool> $decisions */
            $decisions = new \WeakMap();
            $this->editDossierDecisions[$token] = $decisions;
        }

        $decisions = $this->editDossierDecisions[$token];
        $decisions[$dossier] ??= $this->accessDecisionManager->decide($token, [DossierVoter::MODIFICATION], $dossier);

        return $decisions[$dossier];
    }
}
