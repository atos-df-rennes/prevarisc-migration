<?php

namespace App\Validator;

use App\Entity\UtilisateurInformations;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validateur d'unicité conditionnelle pour Utilisateur.
 *
 * Logique métier :
 * - Si login (username) renseigné → vérifier unicité du login
 * - Si login vide → vérifier unicité du mail
 */
final class UniqueUtilisateurValidator extends ConstraintValidator
{
    public function __construct(private readonly UtilisateurRepository $utilisateurRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUtilisateur) {
            throw new UnexpectedTypeException($constraint, UniqueUtilisateur::class);
        }

        if (!$value instanceof Utilisateur) {
            throw new UnexpectedValueException($value, Utilisateur::class);
        }

        $username = $value->getUsername();
        $informations = $value->getInformations();

        // Cas 1 : Login renseigné → vérifier unicité du login
        if ('' !== $username) {
            $existingByUsername = $this->utilisateurRepository->findOneBy([
                'username' => $username,
            ]);

            // Si trouvé ET c'est un autre utilisateur (pas celui en cours d'édition)
            if (null !== $existingByUsername && $existingByUsername->getId() !== $value->getId()) {
                $this->context
                    ->buildViolation($constraint->usernameMessage)
                    ->atPath('username')
                    ->addViolation()
                ;
            }

            return;
        }

        // Cas 2 : Login vide → vérifier unicité du mail
        if ($informations instanceof UtilisateurInformations) {
            $mail = $informations->getMail();

            // Vérifier que le mail est renseigné
            if (null === $mail) {
                // Pas de validation si ni login ni mail (sera géré par @Assert\NotNull si nécessaire)
                return;
            }

            $existingByMail = $this->utilisateurRepository->findExistingByMail($mail, $value);

            if ([] !== $existingByMail) {
                $this->context
                    ->buildViolation($constraint->mailMessage)
                    ->atPath('informations.mail')
                    ->addViolation()
                ;
            }
        }
    }
}
