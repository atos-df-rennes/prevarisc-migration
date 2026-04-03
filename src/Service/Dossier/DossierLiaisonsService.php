<?php

namespace App\Service\Dossier;

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
     * Retourne les dossiers disponibles à lier, groupés par établissement puis par type,
     * en réutilisant findByEtablissementGrouped (même logique que pour les prescriptions).
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
    public function getDossiersDisponiblesGroupesParEtablissement(Dossier $dossier): array
    {
        $liaisonIds = array_map(function (Dossier $lie) {
            return $lie->getId();
        }, $this->getLiaisons($dossier));

        $result = [];

        foreach ($dossier->getEtablissements() as $etab) {
            $groupes = $this->dossierRepository->findByEtablissementGrouped($etab, $dossier);

            foreach (['visites', 'etudes', 'autres'] as $key) {
                $groupes[$key] = array_values(array_filter(
                    $groupes[$key],
                    function (Dossier $d) use ($liaisonIds) {
                        return !in_array($d->getId(), $liaisonIds, true);
                    }
                ));
            }

            if ([] !== $groupes['visites'] || [] !== $groupes['etudes'] || [] !== $groupes['autres']) {
                $result[$etab->getId()] = [
                    'etablissement' => $etab,
                    'visites' => $groupes['visites'],
                    'etudes' => $groupes['etudes'],
                    'autres' => $groupes['autres'],
                ];
            }
        }

        return $result;
    }

    /**
     * Retourne la liste plate des dossiers disponibles à lier (pour la validation du formulaire).
     *
     * @return Dossier[]
     */
    public function getDossiersDisponibles(Dossier $dossier): array
    {
        $disponibles = [];
        foreach ($this->getDossiersDisponiblesGroupesParEtablissement($dossier) as $group) {
            foreach (['visites', 'etudes', 'autres'] as $key) {
                foreach ($group[$key] as $d) {
                    $disponibles[$d->getId()] = $d;
                }
            }
        }

        return array_values($disponibles);
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
