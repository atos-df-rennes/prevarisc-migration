<?php

namespace App\Security\Voter;

use App\Entity\Dossier;
use App\Repository\ResourcesRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DossierVoter extends Voter
{
    public const LECTURE = 'view_doss';
    public const MODIFICATION = 'edit_doss';
    public const VERROUILLAGE = 'verrouillage_dossier';
    public const TOUTES_LES_NATURES = 0;

    /** @var ResourcesRepository */
    private $resourcesRepository;

    public function __construct(ResourcesRepository $resourcesRepository)
    {
        $this->resourcesRepository = $resourcesRepository;
    }

    // @fixme: décommenter le instanceof avant de commit
    protected function supports($attribute, $subject): bool
    {
        if (false === in_array($attribute, [self::LECTURE, self::MODIFICATION, self::VERROUILLAGE], true)) {
            return false;
        }

        $resources = $this->resourcesRepository->findRessourcesSpecialiseesDossier();

        // S'il n'y a pas de ressources spécialisées, tout le monde a accès à tout.
        if ([] === $resources) {
            return false;
        }

        return $subject instanceof Dossier;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        dump('Dossier Voter');
        dd($attribute);

        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::LECTURE:
                // logic to determine if the user can EDIT
                // return true or false
                break;
            case self::MODIFICATION:
                // logic to determine if the user can VIEW
                // return true or false
                break;
            case self::VERROUILLAGE:
                break;
        }

        throw new \LogicException(\sprintf("Le droit %s n'est pas supporté pour les dossiers.", $attribute));
    }
}
