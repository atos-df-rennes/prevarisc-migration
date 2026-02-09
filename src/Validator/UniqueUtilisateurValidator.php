<?php

namespace App\Validator;

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
    /** @var UtilisateurRepository */
    private $utilisateurRepository;

    public function __construct(UtilisateurRepository $utilisateurRepository)
    {
        $this->utilisateurRepository = $utilisateurRepository;
    }

    public function validate($value, Constraint $constraint): void
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
        if (!empty($username)) {
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

                return;
            }
        }

        // Cas 2 : Login vide → vérifier unicité du mail
        if (empty($username) && null !== $informations) {
            $mail = $informations->getMail();

            // Vérifier que le mail est renseigné
            if (empty($mail)) {
                // Pas de validation si ni login ni mail (sera géré par @Assert\NotNull si nécessaire)
                return;
            }

            // Rechercher un utilisateur avec ce mail
            $qb = $this->utilisateurRepository->createQueryBuilder('u');
            $qb->innerJoin('u.informations', 'i')
                ->where('i.mail = :mail')
                ->setParameter('mail', $mail);

            // Exclure l'utilisateur en cours d'édition
            if (null !== $value->getId()) {
                $qb->andWhere('u.id != :id')
                    ->setParameter('id', $value->getId());
            }

            /** @var Utilisateur|null $existingByMail */
            $existingByMail = $qb->getQuery()->getOneOrNullResult();

            if (null !== $existingByMail) {
                $this->context
                    ->buildViolation($constraint->mailMessage)
                    ->atPath('informations.mail')
                    ->addViolation()
                ;
            }
        }
    }
}
