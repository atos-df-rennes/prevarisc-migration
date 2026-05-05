<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;

/**
 * Construit le tableau d'identité Zend_Auth depuis une entité Utilisateur.
 *
 * Le tableau produit est écrit dans $_SESSION['Zend_Auth']['storage']
 * et lu par les contrôleurs Zend legacy via Zend_Auth::getInstance()->getIdentity().
 *
 * @phpstan-type ZendIdentity array{
 *   ID_UTILISATEUR: int|null,
 *   USERNAME_UTILISATEUR: string,
 *   ID_GROUPE: int|null,
 *   ID_UTILISATEURINFORMATIONS: int|null,
 *   ACTIF_UTILISATEUR: bool|null,
 *   FAILED_LOGIN_ATTEMPTS_UTILISATEUR: int|null,
 *   IP_UTILISATEUR: string,
 *   PASSWD_UTILISATEUR: string|null,
 *   uid: int|null,
 *   LASTACTION_UTILISATEUR: string|null,
 *   group: array<string, mixed>,
 *   infos: array<string, mixed>,
 *   preferences: array<string, mixed>
 * }
 */
class ZendIdentityBuilder
{
    /**
     * Construit le tableau d'identité attendu par les contrôleurs Zend legacy.
     *
     * @param Utilisateur $utilisateur L'utilisateur authentifié
     * @param string      $remoteAddr  L'adresse IP cliente
     *
     * @return array<string, mixed>
     */
    public function build(Utilisateur $utilisateur, string $remoteAddr): array
    {
        $groupe = $utilisateur->getGroupe();
        $informations = $utilisateur->getInformations();
        $preferences = $utilisateur->getPreferences();
        $lastAction = $utilisateur->getLastAction();

        $groupeData = [];
        if (null !== $groupe) {
            $groupeData = [
                'LIBELLE_GROUPE' => $groupe->getLibelle(),
                'ID_GROUPE' => $groupe->getId(),
            ];
        }

        $infosData = [];
        if (null !== $informations) {
            $infosData = [
                'NOM_UTILISATEURINFORMATIONS' => $informations->getNom(),
                'PRENOM_UTILISATEURINFORMATIONS' => $informations->getPrenom(),
                'ID_UTILISATEURINFORMATIONS' => $informations->getId(),
            ];
        }

        $preferencesData = [];
        if (null !== $preferences) {
            $preferencesData = [
                'DASHBOARD_BLOCS' => $preferences->getBlocsDashboard(),
                'DERNIERE_VERSION_VUE' => $preferences->getDerniereVersionVue(),
            ];
        }

        return [
            'ID_UTILISATEUR' => $utilisateur->getId(),
            'USERNAME_UTILISATEUR' => $utilisateur->getUsername(),
            'ID_GROUPE' => null !== $groupe ? $groupe->getId() : null,
            'ID_UTILISATEURINFORMATIONS' => null !== $informations ? $informations->getId() : null,
            'ACTIF_UTILISATEUR' => $utilisateur->getActif(),
            'FAILED_LOGIN_ATTEMPTS_UTILISATEUR' => $utilisateur->getFailedLoginAttempts(),
            'IP_UTILISATEUR' => $remoteAddr,
            'PASSWD_UTILISATEUR' => $utilisateur->getPassword(),
            'uid' => $utilisateur->getId(),
            'LASTACTION_UTILISATEUR' => null !== $lastAction
                ? $lastAction->format('Y-m-d H:i:s')
                : null,
            'group' => $groupeData,
            'infos' => $infosData,
            'preferences' => $preferencesData,
        ];
    }
}
