<?php

namespace App\Service;

use App\Constant\TypeChamp;
use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierDisplayRubrique;
use App\Entity\Etablissement;
use App\Entity\EtablissementDisplayRubrique;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\DossierDisplayRubriqueRepository;
use App\Repository\EtablissementDisplayRubriqueRepository;
use App\Repository\ValeurRepository;

class Descriptif
{
    private CapsuleRubriqueRepository $capsuleRubriqueRepository;

    private ValeurRepository $valeurRepository;

    private DossierDisplayRubriqueRepository $dossierDisplayRubriqueRepository;

    private EtablissementDisplayRubriqueRepository $etablissementDisplayRubriqueRepository;

    public function __construct(
        CapsuleRubriqueRepository $capsuleRubriqueRepository,
        ValeurRepository $valeurRepository,
        DossierDisplayRubriqueRepository $dossierDisplayRubriqueRepository,
        EtablissementDisplayRubriqueRepository $etablissementDisplayRubriqueRepository
    ) {
        $this->capsuleRubriqueRepository = $capsuleRubriqueRepository;
        $this->valeurRepository = $valeurRepository;
        $this->dossierDisplayRubriqueRepository = $dossierDisplayRubriqueRepository;
        $this->etablissementDisplayRubriqueRepository = $etablissementDisplayRubriqueRepository;
    }

    /**
     * Charge les rubriques/champs/valeurs pour un dossier et une capsule donnée.
     *
     * @return array{capsule: CapsuleRubrique|null, rubriques: list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}>}
     */
    public function getForDossier(Dossier $dossier, string $capsuleNomInterne): array
    {
        return $this->buildData(
            $capsuleNomInterne,
            $this->valeurRepository->findByDossier($dossier),
            $this->buildDisplayPrefs($this->dossierDisplayRubriqueRepository->findBy(['dossier' => $dossier]))
        );
    }

    /**
     * Charge les rubriques/champs/valeurs pour un établissement et une capsule donnée.
     *
     * @return array{capsule: CapsuleRubrique|null, rubriques: list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}>}
     */
    public function getForEtablissement(Etablissement $etablissement, string $capsuleNomInterne): array
    {
        return $this->buildData(
            $capsuleNomInterne,
            $this->valeurRepository->findByEtablissement($etablissement),
            $this->buildDisplayPrefs($this->etablissementDisplayRubriqueRepository->findBy(['etablissement' => $etablissement]))
        );
    }

    /**
     * Logique commune : charge la capsule et assemble la structure finale.
     *
     * Les valeurs sont pré-chargées en une seule requête et indexées par champId
     * pour éviter les requêtes N+1 lors de l'assemblage.
     *
     * @param array<int, Valeur[]> $valeurs      valeurs indexées par champId
     * @param array<int, bool>     $displayPrefs prefs indexées par rubriqueId
     *
     * @return array{capsule: CapsuleRubrique|null, rubriques: list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}>}
     */
    private function buildData(string $capsuleNomInterne, array $valeurs, array $displayPrefs): array
    {
        $capsule = $this->capsuleRubriqueRepository->findByNomInterneWithFullData($capsuleNomInterne);

        if (null === $capsule) {
            return ['capsule' => null, 'rubriques' => []];
        }

        return [
            'capsule' => $capsule,
            'rubriques' => $this->buildRubriques($capsule, $valeurs, $displayPrefs),
        ];
    }

    /**
     * Construit la structure rubriques enrichie avec display et valeurs résolues.
     *
     * @param array<int, Valeur[]> $valeurs      valeurs indexées par champId
     * @param array<int, bool>     $displayPrefs prefs indexées par rubriqueId
     *
     * @return list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}>
     */
    private function buildRubriques(CapsuleRubrique $capsule, array $valeurs, array $displayPrefs): array
    {
        $result = [];

        foreach ($capsule->getRubriques() as $rubrique) {
            $display = $displayPrefs[$rubrique->getId()] ?? $rubrique->getDefaultDisplay() ?? true;

            $champs = [];
            foreach ($rubrique->getRootChamps() as $champ) {
                $champs[] = $this->buildChamp($champ, $valeurs);
            }

            $result[] = [
                'rubrique' => $rubrique,
                'display' => $display,
                'champs' => $champs,
            ];
        }

        return $result;
    }

    /**
     * Construit la structure d'un champ avec sa valeur résolue.
     * Pour les champs Parent (type 6), construit la structure tableau ou groupe.
     *
     * @param array<int, Valeur[]> $valeurs
     *
     * @return array<string, mixed>
     */
    private function buildChamp(Champ $champ, array $valeurs): array
    {
        $type = $champ->getType();
        if (null !== $type && TypeChamp::PARENT === $type->getId()) {
            return $this->buildParentChamp($champ, $valeurs);
        }

        return [
            'champ' => $champ,
            'valeur' => $this->resolveValeur($champ, $valeurs),
        ];
    }

    /**
     * Construit la structure d'un champ Parent (tableau ou groupe de sous-champs).
     *
     * @param array<int, Valeur[]> $valeurs
     *
     * @return array<string, mixed>
     */
    private function buildParentChamp(Champ $champ, array $valeurs): array
    {
        $enfants = $champ->getEnfants()->toArray();

        if ($champ->getTableau()) {
            return [
                'champ' => $champ,
                'valeur' => null,
                'lignes' => $this->buildTableauLignes($enfants, $valeurs),
            ];
        }

        $sousChamps = [];
        foreach ($enfants as $enfant) {
            $sousChamps[] = [
                'champ' => $enfant,
                'valeur' => $this->resolveValeur($enfant, $valeurs),
            ];
        }

        return [
            'champ' => $champ,
            'valeur' => null,
            'sous_champs' => $sousChamps,
        ];
    }

    /**
     * Construit les lignes d'un tableau (champs enfants avec plusieurs valeurs par idx).
     *
     * @param Champ[]              $colonnes
     * @param array<int, Valeur[]> $valeurs
     *
     * @return list<array<int, Valeur>>
     */
    private function buildTableauLignes(array $colonnes, array $valeurs): array
    {
        $lignes = [];

        foreach ($colonnes as $colonne) {
            $champId = $colonne->getId();
            if (null === $champId) {
                continue;
            }

            $valeursColonne = $valeurs[$champId] ?? [];
            foreach ($valeursColonne as $valeur) {
                $idx = (int) ($valeur->getIdx() ?? 0);
                $lignes[$idx][$champId] = $valeur;
            }
        }

        ksort($lignes);

        return array_values($lignes);
    }

    /**
     * Résout la valeur effective d'un champ (premier enregistrement, toutes colonnes confondues).
     *
     * @param array<int, Valeur[]> $valeurs
     */
    private function resolveValeur(Champ $champ, array $valeurs): ?Valeur
    {
        $champValeurs = $valeurs[$champ->getId()] ?? [];

        return $champValeurs[0] ?? null;
    }

    /**
     * Construit un index [rubriqueId => bool] depuis les préférences d'affichage.
     *
     * @param DossierDisplayRubrique[]|EtablissementDisplayRubrique[] $displayRubriques
     *
     * @return array<int, bool>
     */
    private function buildDisplayPrefs(array $displayRubriques): array
    {
        $prefs = [];
        foreach ($displayRubriques as $displayRubrique) {
            $rubrique = $displayRubrique->getRubrique();
            $rubriqueId = null !== $rubrique ? $rubrique->getId() : null;
            if (null !== $rubriqueId) {
                $prefs[$rubriqueId] = (bool) $displayRubrique->getUserDisplay();
            }
        }

        return $prefs;
    }
}
