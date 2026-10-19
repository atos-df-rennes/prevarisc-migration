<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;

/**
 * Encodeur MD5 legacy compatible avec le format de hachage Prévarisc.
 *
 * Format : md5(username . globalSalt . password)
 * Le username est retourné par Utilisateur::getSalt() en tant que $salt.
 *
 * Utilisé via migrate_from dans security.php pour migrer automatiquement
 * les anciens hashs MD5 vers bcrypt lors de la prochaine connexion réussie.
 */
final class Md5SaltedEncoder implements LegacyPasswordHasherInterface
{
    /**
     * @param string $globalSalt La valeur de PREVARISC_SECURITY_SALT
     */
    public function __construct(private readonly string $globalSalt)
    {
    }

    /**
     * Encode le mot de passe avec le format legacy MD5.
     *
     * @param string      $raw  Le mot de passe en clair
     * @param string|null $salt Le username retourné par Utilisateur::getSalt()
     *
     * @return string Le hash MD5
     */
    #[\Deprecated(message: 'utilisez un algorithme moderne à la place', since: '3.0.0')]
    public function encodePassword($raw, $salt): string
    {
        return md5($salt.$this->globalSalt.$raw);
    }

    #[\Deprecated(message: 'utilisez un algorithme moderne à la place', since: '3.0.0')]
    public function hash(string $plainPassword, ?string $salt = null): string
    {
        return $this->encodePassword($plainPassword, $salt);
    }

    /**
     * Vérifie si le mot de passe en clair correspond au hash stocké.
     *
     * @param string      $encoded Le hash stocké en base (32 chars MD5)
     * @param string      $raw     Le mot de passe en clair à vérifier
     * @param string|null $salt    Le username retourné par Utilisateur::getSalt()
     */
    #[\Deprecated(message: 'utilisez un algorithme moderne à la place', since: '3.0.0')]
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        return hash_equals($encoded, $this->encodePassword($raw, $salt));
    }

    #[\Deprecated(message: 'utilisez un algorithme moderne à la place', since: '3.0.0')]
    public function verify(string $hashedPassword, string $plainPassword, ?string $salt = null): bool
    {
        return $this->isPasswordValid($hashedPassword, $plainPassword, $salt);
    }

    #[\Deprecated(message: 'utilisez un algorithme moderne à la place', since: '3.0.0')]
    public function needsRehash(string $hashedPassword): bool
    {
        return true;
    }
}
