<?php

namespace App\Validator;

use App\Entity\Dossier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator pour le champ OBJET du dossier.
 *
 * Le champ OBJET est obligatoire pour toutes les natures de dossier
 * SAUF les natures 21 et 26 (visites périodiques).
 *
 * @see prevarisc/application/controllers/DossierController.php ligne 901-903
 */
final class DossierObjetValidator extends ConstraintValidator
{
    /**
     * IDs des natures pour lesquelles OBJET n'est PAS requis.
     *
     * - Nature 21 : Visite périodique
     * - Nature 26 : Groupe visite périodique
     *
     * @var int[]
     */
    private const NATURES_SANS_OBJET = [21, 26];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DossierObjet) {
            throw new UnexpectedTypeException($constraint, DossierObjet::class);
        }

        if (!$value instanceof Dossier) {
            throw new UnexpectedValueException($value, Dossier::class);
        }

        // Récupère la nature du dossier (un dossier n'a qu'une nature)
        $nature = $value->getNature();
        if (null === $nature) {
            // Si pas de nature, pas de validation (sera validée par @Assert\Count sur nature)
            return;
        }

        $natureId = $nature->getId();

        // Si la nature est dans la liste des natures sans objet, pas de validation
        if (in_array($natureId, self::NATURES_SANS_OBJET, true)) {
            return;
        }

        // Pour toutes les autres natures, l'objet est obligatoire
        $objet = $value->getObjet();
        if (null === $objet || '' === trim($objet)) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('objet')
                ->addViolation()
            ;
        }
    }
}
