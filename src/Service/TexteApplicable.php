<?php

namespace App\Service;

use App\Entity\PrescriptionTexteListe;
use App\Entity\TexteApplicable as TexteApplicableEntity;
use Doctrine\ORM\EntityManagerInterface;

final class TexteApplicable
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(TexteApplicableEntity $texteApplicable, bool $prescription): void
    {
        if ($prescription) {
            $prescriptionListe = new PrescriptionTexteListe();
            $prescriptionListe->setLibelle($texteApplicable->getLibelle() ?: '');
            $this->entityManager->persist($prescriptionListe);
        }

        $this->entityManager->persist($texteApplicable);
        $this->entityManager->flush();
    }

    /**
     * @param array<TexteApplicableEntity> $textesApplicablesValues
     * @return array{}|array{int, array{'titre': ?string, 'textes': array{int, ?string}}}
     */
    public function trierParType(array $textesApplicablesValues): array
    {
        $textesApplicables = [];
        $lastType = '';

        foreach ($textesApplicablesValues as $texteApplicable) {
            if (!$texteApplicable->getVisible()) {
                continue;
            }

            if (null === $texteApplicable->getType()) {
                continue;
            }

            if ('' !== $lastType && $lastType === $texteApplicable->getType()) {
                // @fixme: Créer un DTO pour corriger l'erreur PHPStan
                /* @phpstan-ignore-next-line */
                $textesApplicables[$texteApplicable->getType()->getId()]['textes'][] = $texteApplicable->getLibelle();

                continue;
            }

            $textesApplicables[$texteApplicable->getType()->getId()] = [
                'titre' => $texteApplicable->getType()->getLibelle(),
                'textes' => [$texteApplicable->getLibelle()]
            ];
            $lastType = $texteApplicable->getType();
        }

        return $textesApplicables;
    }
}
