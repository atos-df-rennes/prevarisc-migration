<?php

namespace App\Security\Voter;

use App\Entity\Dossier;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DossierVoter extends Voter
{
    public const LECTURE = 'view_doss';

    public const MODIFICATION = 'edit_doss';

    /**
     * Vérifie le privilège edit_doss sans tenir compte du verrouillage.
     * Utilisé pour les actions qui, dans le legacy, ne sont pas bloquées par le verrou
     * (textes applicables, vérifications techniques, effectifs/dégagements, avis/dérogations, génération rapport).
     */
    public const MODIFICATION_IGNORE_VERROU = 'edit_doss_ignore_verrou';

    public const VERROUILLAGE = 'verrouillage_dossier';

    /**
     * @var Resources[]
     */
    private array $resources = [];

    public function __construct(private readonly ResourcesRepository $resourcesRepository, private readonly PrivilegesRepository $privilegesRepository, private readonly EtablissementCollectionAccessChecker $collectionAccessChecker)
    {
    }

    protected function supports($attribute, $subject): bool
    {
        if (false === in_array($attribute, [self::LECTURE, self::MODIFICATION, self::MODIFICATION_IGNORE_VERROU, self::VERROUILLAGE], true)) {
            return false;
        }

        $this->resources = $this->resourcesRepository->findRessourcesSpecialiseesSimplesDossier();

        // S'il n'y a pas de ressources spécialisées, tout le monde a accès à tout.
        if ([] === $this->resources) {
            return false;
        }

        return $subject instanceof Dossier;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        if (!$subject instanceof Dossier) {
            return false;
        }

        $natureDossier = $subject->getNature();

        if (null === $natureDossier) {
            return false;
        }

        $natureDossierId = $natureDossier->getId();

        if (null === $natureDossierId) {
            return false;
        }

        $userGroup = $user->getGroupe();

        if (null === $userGroup) {
            return false;
        }

        $userPrivileges = $userGroup->getPrivileges()->toArray();

        // Indexer les ressources par identifiant
        /** @var array<int, Resources> $resourcesById */
        $resourcesById = [];
        foreach ($this->resources as $resource) {
            $name = $resource->getName();

            if (null === $name) {
                continue;
            }

            $parts = explode('_', $name);
            if (count($parts) < 2) {
                continue;
            }

            $natureIdStr = $parts[1];
            $natureId = (int) $natureIdStr;
            $resourcesById[$natureId] = $resource;
        }

        if (!$this->checkEtablissementsAccess($subject, $token)) {
            return false;
        }

        if (self::MODIFICATION === $attribute) {
            return $this->canEdit($subject, $userPrivileges, $resourcesById, $natureDossierId);
        }

        if (self::MODIFICATION_IGNORE_VERROU === $attribute) {
            return $this->canView(self::MODIFICATION, $userPrivileges, $resourcesById, $natureDossierId);
        }

        return $this->canView($attribute, $userPrivileges, $resourcesById, $natureDossierId);
    }

    /**
     * Vérifie que l'utilisateur a le droit view_ets sur au moins un établissement lié au dossier.
     * Aligne sur le comportement du Plugin_ACL legacy (lignes 278–302) : si le dossier n'a aucun
     * établissement lié, la vérification est ignorée (cas $i === 0 du legacy).
     */
    private function checkEtablissementsAccess(Dossier $dossier, TokenInterface $token): bool
    {
        $etablissements = $dossier->getEtablissements();

        if ($etablissements->isEmpty()) {
            return true;
        }

        return $this->collectionAccessChecker->hasAtLeastOneAccessible($etablissements, EtablissementVoter::LECTURE, $token);
    }

    /**
     * Vérifie si un utilisateur dispose d'un privilège donné sur un dossier selon sa nature.
     *
     * @param string                $attribute       Nom du privilège à vérifier
     * @param mixed[]               $userPrivileges  Liste des privilèges de l'utilisateur
     * @param array<int, Resources> $resourcesById   Ressources indexées par identifiant de nature
     * @param int                   $natureDossierId Identifiant de la nature du dossier
     */
    private function canView(string $attribute, array $userPrivileges, array $resourcesById, int $natureDossierId): bool
    {
        // Vérification du privilège "toutes natures" (id=0)
        if (array_key_exists(0, $resourcesById)) {
            $privilegeAll = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resourcesById[0]]);
            if ($privilegeAll && in_array($privilegeAll, $userPrivileges, true)) {
                return true;
            }
        }

        // Vérification du privilège spécifique à la nature du dossier
        if (array_key_exists($natureDossierId, $resourcesById)) {
            $privilegeSpecific = $this->privilegesRepository->findOneBy(['name' => $attribute, 'resource' => $resourcesById[$natureDossierId]]);
            if ($privilegeSpecific && in_array($privilegeSpecific, $userPrivileges, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur peut modifier un dossier :
     * le dossier ne doit pas être verrouillé ET le privilège edit_doss doit être accordé.
     *
     * @param Dossier               $dossier         Dossier cible
     * @param mixed[]               $userPrivileges  Liste des privilèges de l'utilisateur
     * @param array<int, Resources> $resourcesById   Ressources indexées par identifiant de nature
     * @param int                   $natureDossierId Identifiant de la nature du dossier
     */
    private function canEdit(Dossier $dossier, array $userPrivileges, array $resourcesById, int $natureDossierId): bool
    {
        return !$dossier->hasVerrou() && $this->canView(self::MODIFICATION, $userPrivileges, $resourcesById, $natureDossierId);
    }
}
