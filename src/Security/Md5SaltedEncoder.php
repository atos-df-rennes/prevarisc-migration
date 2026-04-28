<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Encodeur MD5 legacy compatible avec le format de hachage Prévarisc.
 *
 * Format : md5(username . globalSalt . password)
 * Le username est retourné par Utilisateur::getSalt() en tant que $salt.
 *
 * Utilisé via migrate_from dans security.php pour migrer automatiquement
 * les anciens hashs MD5 vers bcrypt lors de la prochaine connexion réussie.
 */
class Md5SaltedEncoder implements PasswordEncoderInterface
{
    /** @var string */
    private $globalSalt;

    /**
     * @param string $globalSalt La valeur de PREVARISC_SECURITY_SALT
     */
    public function __construct(string $globalSalt)
    {
        $this->globalSalt = $globalSalt;
    }

    /**
     * Encode le mot de passe avec le format legacy MD5.
     *
     * @param string      $raw  Le mot de passe en clair
     * @param string|null $salt Le username retourné par Utilisateur::getSalt()
     *
     * @return string Le hash MD5
     */
    public function encodePassword($raw, $salt): string
    {
        return md5($salt.$this->globalSalt.$raw);
    }

    /**
     * Vérifie si le mot de passe en clair correspond au hash stocké.
     *
     * @param string      $encoded Le hash stocké en base (32 chars MD5)
     * @param string      $raw     Le mot de passe en clair à vérifier
     * @param string|null $salt    Le username retourné par Utilisateur::getSalt()
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        return hash_equals($encoded, $this->encodePassword($raw, $salt));
    }

    /**
     * Indique qu'un re-hash est toujours nécessaire.
     * Déclenche la migration automatique MD5 → bcrypt à chaque connexion réussie.
     *
     * @param string $encoded Le hash actuel (ignoré)
     *
     * @return bool Toujours true
     */
    public function needsRehash(string $encoded): bool
    {
        return true;
    }
}
