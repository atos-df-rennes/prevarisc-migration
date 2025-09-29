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
     * @param array<int, PrescriptionReglementaireAssociation|PrescriptionTexteListe|PrescriptionArticleListe> $items
     *
     * @return array<int, array{association: PrescriptionReglementaireAssociation, texte: PrescriptionTexteListe, article: PrescriptionArticleListe}>
     */
    private function buildAssociationsFromRaw(array $items): array
    {
        // Clés normalisées pour éviter les "magic strings"
        $KEY_ASSOC = 'association';
        $KEY_TEXTE = 'texte';
        $KEY_ARTICLE = 'article';

        // Indexation préalable pour éviter une double boucle
        $textesById = $this->indexByIdOfType($items, PrescriptionTexteListe::class);
        $articlesById = $this->indexByIdOfType($items, PrescriptionArticleListe::class);

        $assembled = [];

        foreach ($items as $item) {
            if (!$item instanceof PrescriptionReglementaireAssociation) {
                continue;
            }

            $current = [$KEY_ASSOC => $item];
            $texteId = $item->getTexte();
            if (null !== $texteId && isset($textesById[$texteId])) {
                $current[$KEY_TEXTE] = $textesById[$texteId];
            }

            $articleId = $item->getArticle();
            if (null !== $articleId && isset($articlesById[$articleId])) {
                $current[$KEY_ARTICLE] = $articlesById[$articleId];
            }

            $assembled[] = $current;
        }

        return $assembled;
    }

    /**
     * Indexe les éléments d'un type donné par leur identifiant.
     *
     * @param array  $items Liste hétérogène d'objets
     * @param string $class Nom de la classe à filtrer (ex: PrescriptionTexteListe::class)
     *
     * @return array<int, object> tableau [id => objet]
     */
    private function indexByIdOfType(array $items, string $class): array
    {
        $index = [];
        foreach ($items as $it) {
            if ($it instanceof $class) {
                // Tous les types ciblés exposent getId(): ?int
                $id = $it->getId();
                if (null !== $id) {
                    $index[$id] = $it;
                }
            }
        }

        return $index;
    }
}
