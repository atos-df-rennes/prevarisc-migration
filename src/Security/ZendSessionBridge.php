<?php

namespace App\Security;

/**
 * Encapsule l'accès à la session Zend_Auth héritée.
 *
 * Isole la dépendance à $_SESSION du reste du code Symfony pour la rendre
 * injectable, remplaçable et testable unitairement.
 *
 * @phpstan-type ZendAuth array{
 *   storage: array{
 *     USERNAME_UTILISATEUR: string
 *   }
 * }
 */
class ZendSessionBridge
{
    /**
     * Retourne le nom d'utilisateur issu de la session Zend, ou null si absent.
     */
    public function getAuthenticatedUsername(): ?string
    {
        $zendAuth = $_SESSION['Zend_Auth'] ?? null;
        if (!is_array($zendAuth)) {
            return null;
        }

        /** @var ZendAuth $zendAuth */
        return $zendAuth['storage']['USERNAME_UTILISATEUR'];
    }

    /**
     * Indique si une session Zend_Auth est active.
     */
    public function isAuthenticated(): bool
    {
        return null !== $this->getAuthenticatedUsername();
    }
}
