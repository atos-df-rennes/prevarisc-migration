<?php

namespace App\Service;

use App\Entity\PrescriptionTexteListe;
use App\Repository\PrescriptionArticleListeRepository;
use App\Repository\PrescriptionReglementaireAssociationRepository;
use App\Repository\PrescriptionReglementaireRepository;
use App\Repository\PrescriptionTexteListeRepository;

final class Prescriptions
{
    /** @var PrescriptionTexteListeRepository */
    private $prescriptionTexteListeRepository;

    /** @var PrescriptionArticleListeRepository */
    private $prescriptionArticleListeRepository;

    /** @var PrescriptionReglementaireRepository */
    private $prescriptionReglementaireRepository;

    /** @var PrescriptionReglementaireAssociationRepository */
    private $prescriptionReglementaireAssociationRepository;


    public function __construct(
        PrescriptionTexteListeRepository $prescriptionTexteListeRepository,
        PrescriptionArticleListeRepository $prescriptionArticleListeRepository,
        PrescriptionReglementaireRepository $prescriptionReglementaireRepository,
        PrescriptionReglementaireAssociationRepository $prescriptionReglementaireAssociationRepository
    ) {
        $this->prescriptionTexteListeRepository = $prescriptionTexteListeRepository;
        $this->prescriptionArticleListeRepository = $prescriptionArticleListeRepository;
        $this->prescriptionReglementaireRepository = $prescriptionReglementaireRepository;
        $this->prescriptionReglementaireAssociationRepository = $prescriptionReglementaireAssociationRepository;
    }


    /**
     * @return PrescriptionTexteListe[]
     */
    public function getTextesListe(): array
    {
        return $this->prescriptionTexteListeRepository->findAllTextes();
    }

    /**
     * @return PrescriptionArticleListe[]
     */
    public function getArticlesListe(?bool $isVisible = null): array
    {
        $criteria = [];
        if (null !== $isVisible) {
            $criteria['visible'] = $isVisible;
        }

        return $this->prescriptionArticleListeRepository->findBy($criteria, ['libelle' => 'ASC']);
    }

    /**
     * Récupère les prescriptions et leurs associations article/texte.
     *
     * @return array<int, array{prescription: object, associations: array<int, array{association: PrescriptionReglementaireAssociation, texte: PrescriptionTexteListe, article: PrescriptionArticleListe}>}>
     */
    public function getPrescriptions(string $type, ?bool $mode = null): array
    {
        $prescriptions = $this->prescriptionReglementaireRepository->recupPrescRegl($type, $mode);

        $result = [];
        foreach ($prescriptions as $prescription) {
            if (null === ($id = $prescription->getId())) {
                continue;
            }

            $rawAssocItems = $this->prescriptionReglementaireAssociationRepository
                ->getPrescriptionReglAssoc($id);

            $associations = $this->buildAssociationsFromRaw($rawAssocItems);

            if (!empty($associations)) {
                $result[] = [
                    'prescription' => $prescription,
                    'associations' => $associations,
                ];
            }
        }

        return $result;
    }

    /**
     * Construit un tableau d'associations [association, texte, article] à partir
     * de la collection renvoyée par le repository (pra, ptl, pal).
     *
     * @param array<int, PrescriptionReglementaireAssociation|PrescriptionTexteListe|PrescriptionArticleListe> $rawAssocItems
     *
     * @return array<int, array{association: PrescriptionReglementaireAssociation, texte: PrescriptionTexteListe, article: PrescriptionArticleListe}>
     */
    private function buildAssociationsFromRaw(array $rawAssocItems): array
    {
        // Clés normalisées pour éviter les "magic strings"
        $KEY_ASSOC = 'association';
        $KEY_TEXTE = 'texte';
        $KEY_ARTICLE = 'article';

        $assembled = [];
        $current = [];

        foreach ($rawAssocItems as $item) {
            if ($item instanceof PrescriptionReglementaireAssociation) {
                $current[$KEY_ASSOC] = $item;
            } elseif ($item instanceof PrescriptionTexteListe) {
                $current[$KEY_TEXTE] = $item;
            } elseif ($item instanceof PrescriptionArticleListe) {
                $current[$KEY_ARTICLE] = $item;
            }

            // Quand les 3 parties sont présentes, on pousse et on réinitialise
            if (isset($current[$KEY_ASSOC], $current[$KEY_TEXTE], $current[$KEY_ARTICLE])) {
                $assembled[] = $current;
                $current = [];
            }
        }

        return $assembled;
    }
}
