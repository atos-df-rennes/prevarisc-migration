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
    private string $globalSalt;

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
     * Indique qu'aucun re-hash n'est nécessaire pendant la période de transition.
     *
     * Retourne false pour bloquer la migration automatique MD5 → bcrypt sur login,
     * ce qui garantit que le fallback legacy (PREVARISC_SYMFONY_LOGIN=0) reste
     * opérationnel à tout moment.
     *
     * À passer à true (ou supprimer cette méthode) une fois le legacy retiré.
     *
     * @param string $encoded Le hash actuel (ignoré)
     *
     * @return bool Toujours false pendant la transition
     */
    public function needsRehash(string $encoded): bool
    {
        return false;
    }
}
