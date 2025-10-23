<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class EtablissementVoter extends Voter
{
    public const LECTURE = 'view_ets';

    public const MODIFICATION = 'edit_ets';

    public const REGLE_ERP = '^etablissement_erp_[0-9]+(-[0-9]+)*_[0-5](_[01]){3}$';

    public const REGLE_CELLULE = '^etablissement_cell_[0-9]+(-[0-9]+)*_0$';

    public const REGLE_HABITATION = '^etablissement_hab_[0-9]+(-[0-9]+)*(_[01]){2}$';

    public const REGLE_IGH = '^etablissement_igh_[0-9]+(-[0-9]+)*(_[01]){3}$';

    public const REGLE_BUP = '^etablissement_bup(_[01]){2}$';

    public const REGLE_CAMPING = '^etablissement_camp_0_0$';

    public const REGLE_MANIFESTATION_TEMPORAIRE = '^etablissement_temp_0_0$';

    public const REGLE_IOP = '^etablissement_iop_0_0$';

    public const REGLE_ZONE = '^etablissement_zone_[0-9]+(-[0-9]+)*_0_0$';

    protected function supports($attribute, $subject): bool
    {
        if (false === in_array($attribute, [self::LECTURE, self::MODIFICATION], true)) {
            return false;
        }

        return $subject instanceof Etablissement;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        return $subject instanceof Etablissement;
    }
}
