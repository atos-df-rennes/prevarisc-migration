<?php

namespace App\Service\Dossier;

use App\Constant\DossierType as DossierTypeConstant;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DossierLiaisonsService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierRepository */
    private $dossierRepository;

    public function __construct(EntityManagerInterface $entityManager, DossierRepository $dossierRepository)
    {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
    }

    /**
     * Retourne tous les dossiers liés au dossier donné (les deux côtés de la relation).
     *
     * @return Dossier[]
     */
    public function getLiaisons(Dossier $dossier): array
    {
        $lies = array_merge(
            $dossier->getDossiersLies()->toArray(),
            $dossier->getDossiersAyantLie()->toArray()
        );

        $unique = [];
        foreach ($lies as $lie) {
            $unique[$lie->getId()] = $lie;
        }

        return array_values($unique);
    }

    /**
     * Retourne les dossiers des mêmes établissements qui ne sont pas encore liés.
     *
     * @return Dossier[]
     */
    public function getDossiersDisponibles(Dossier $dossier): array
    {
        $excludedIds = [];
        if (null !== $dossier->getId()) {
            $excludedIds[] = $dossier->getId();
        }
        foreach ($this->getLiaisons($dossier) as $lie) {
            if (null !== $lie->getId()) {
                $excludedIds[] = $lie->getId();
            }
        }

        return $this->dossierRepository->findByEtablissementsExcluding($dossier, $excludedIds);
    }

    /**
     * Retourne les dossiers disponibles à lier, groupés par établissement puis par type
     * (visites, etudes, autres).
     *
     * Structure retournée :
     * [
     *   etab_id => [
     *     'etablissement' => Etablissement,
     *     'visites'       => Dossier[],
     *     'etudes'        => Dossier[],
     *     'autres'        => Dossier[],
     *   ],
     *   ...
     * ]
     *
     * @return array<int, array{etablissement: Etablissement, visites: Dossier[], etudes: Dossier[], autres: Dossier[]}>
     */
    public function getDossiersDisponiblesParEtablissement(Dossier $dossier): array
    {
        $disponibles = $this->getDossiersDisponibles($dossier);

        /** @var array<int, array{etablissement: Etablissement, visites: Dossier[], etudes: Dossier[], autres: Dossier[]}> $grouped */
        $grouped = [];

        foreach ($disponibles as $d) {
            foreach ($d->getEtablissements() as $etab) {
                $etabId = $etab->getId();
                if (!isset($grouped[$etabId])) {
                    $grouped[$etabId] = [
                        'etablissement' => $etab,
                        'visites' => [],
                        'etudes' => [],
                        'autres' => [],
                    ];
                }

                $typeId = null !== $d->getType() ? $d->getType()->getId() : null;

                if (null !== $typeId && in_array($typeId, DossierTypeConstant::TYPES_VISITE, true)) {
                    $grouped[$etabId]['visites'][] = $d;
                } elseif (DossierTypeConstant::ETUDE === $typeId) {
                    $grouped[$etabId]['etudes'][] = $d;
                } else {
                    $grouped[$etabId]['autres'][] = $d;
                }
            }
        }

        return $grouped;
    }

    /**
     * Retourne la date de référence d'un dossier et son texte descriptif.
     * Priorité : date commission > date visite > date de création.
     *
     * @return array{texte: string, date: string}
     */
    public function getDossierDateReference(Dossier $dossier): array
    {
        if (null !== $dossier->getDateCommission()) {
            $dateStr = substr($dossier->getDateCommission(), 0, 10);
            $date = '0000-00-00' !== $dateStr
                ? (new \DateTime($dateStr))->format('d/m/Y')
                : '00/00/0000';

            return ['texte' => 'date de passage en commission', 'date' => $date];
        }

        if (null !== $dossier->getDateVisite()) {
            return [
                'texte' => 'date de visite',
                'date' => $dossier->getDateVisite()->format('d/m/Y'),
            ];
        }

        $dateInsert = $dossier->getDateInsert();
        $date = null !== $dateInsert ? $dateInsert->format('d/m/Y') : '00/00/0000';

        return ['texte' => 'créé le', 'date' => $date];
    }

    /**
     * Lie plusieurs dossiers au dossier donné.
     *
     * @param Dossier[] $dossiersToAdd
     */
    public function addLiaisons(Dossier $dossier, array $dossiersToAdd): void
    {
        foreach ($dossiersToAdd as $dossierToAdd) {
            $dossier->addDossiersLies($dossierToAdd);
        }

        $this->entityManager->flush();
    }

    /**
     * Supprime le lien entre deux dossiers (peu importe le sens de la relation).
     */
    public function removeLiaison(Dossier $dossier, Dossier $dossierLie): void
    {
        if ($dossier->getDossiersLies()->contains($dossierLie)) {
            $dossier->removeDossiersLies($dossierLie);
        } elseif ($dossierLie->getDossiersLies()->contains($dossier)) {
            $dossierLie->removeDossiersLies($dossier);
        }

        $this->entityManager->flush();
    }

    /**
     * Ajoute un établissement lié au dossier.
     */
    public function addEtablissement(Dossier $dossier, Etablissement $etablissement): void
    {
        $dossier->addEtablissement($etablissement);
        $this->entityManager->flush();
    }

    /**
     * Supprime le lien entre un dossier et un établissement.
     */
    public function removeEtablissement(Dossier $dossier, Etablissement $etablissement): void
    {
        $dossier->removeEtablissement($etablissement);
        $this->entityManager->flush();
    }
}
