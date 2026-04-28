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
 *     'USERNAME_UTILISATEUR'?: string|null,
 *     ...
 *   },
 *   ...
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
        return $zendAuth['storage']['USERNAME_UTILISATEUR'] ?? null;
    }

    /**
     * Indique si une session Zend_Auth est active.
     */
    public function isAuthenticated(): bool
    {
        return null !== $this->getAuthenticatedUsername();
    }

    /**
     * Écrit le tableau d'identité dans $_SESSION['Zend_Auth']['storage'].
     *
     * Utilisé par FormLoginAuthenticator pour propager l'authentification
     * Symfony vers les contrôleurs Zend legacy.
     *
     * @param array<string, mixed> $identity Le tableau retourné par ZendIdentityBuilder::build()
     */
    public function writeAuthentication(array $identity): void
    {
        if (!isset($_SESSION['Zend_Auth']) || !is_array($_SESSION['Zend_Auth'])) {
            $_SESSION['Zend_Auth'] = [];
        }

        $_SESSION['Zend_Auth']['storage'] = $identity;
    }

    /**
     * Efface complètement la session Zend_Auth.
     *
     * Utilisé lors du logout pour invalider l'identité Zend
     * en parallèle du token Symfony.
     */
    public function clearAuthentication(): void
    {
        unset($_SESSION['Zend_Auth']);
    }
}
