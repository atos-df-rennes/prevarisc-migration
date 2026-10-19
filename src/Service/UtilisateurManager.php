<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UtilisateurManager
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Applique la logique d'enregistrement du mot de passe avant persistance.
     * - Si l'utilisateur est SSO -> password = null
     * - Sinon, si plainPassword renseigné non vide -> encoder et setPassword
     * - Sinon, ne pas modifier le password (permet de conserver l'existant).
     */
    public function processPasswordBeforePersist(Utilisateur $utilisateur): void
    {
        if ($utilisateur->getSso()) {
            $utilisateur->setPassword(null);

            return;
        }

        $plain = $utilisateur->getPlainPassword();
        if (null !== $plain && '' !== $plain) {
            // Utilisateur::getSalt() retourne le username pour l'encodeur MD5 legacy
            $utilisateur->setPassword($this->hasher->hashPassword($utilisateur, $plain));
        }
    }
}
