<?php

namespace App\Service\Odt;

use App\Constant\TypeChamp;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use App\Service\Formulaires\Fusion\ChampNamer;
use App\Service\Formulaires\ValeurResolver;
use Odtphp\Odf;
use Odtphp\Segment;
use Psr\Log\LoggerInterface;

/**
 * Service commun pour l'écriture de variables et formulaires dans les documents ODT.
 *
 * Centralise la logique partagée entre DossierRapportOdtService et
 * CommissionDocumentGenerationService.
 */
class OdtWriter
{
    /**
     * Écrit une variable dans un document ODT (Odf ou Segment).
     *
     * @param Odf|Segment          $target Cible ODT
     * @param string               $name   Nom du champ dans le template
     * @param string|null          $value  Valeur à injecter
     * @param LoggerInterface|null $logger Logger optionnel (canal rapport)
     */
    public function setVariable($target, string $name, ?string $value, ?LoggerInterface $logger = null): void
    {
        try {
            $target->setVars($name, $value ?? '', true, 'UTF8');
            if ($logger instanceof LoggerInterface) {
                $prefix = $target instanceof Segment ? '['.$target->getName().'] ' : '';
                $logger->debug('{prefix}{name} : {value}', [
                    'prefix' => $prefix,
                    'name' => '{'.$name.'}',
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (\Exception) {
            // Silencieux — variable absente du template ODT
        }
    }

    /**
     * Applique les formulaires personnalisés (capsules/rubriques) dans un Odf racine.
     *
     * Accès aux segments tableaux via $odf->setSegment() + $odf->mergeSegment().
     *
     * @param array<string, array{capsule: mixed, rubriques: array<int, array{rubrique: Rubrique, champs: array<int, array<string, mixed>>}>}> $capsules
     */
    public function applyFormulairesToOdf(Odf $odf, array $capsules): void
    {
        foreach ($capsules as $nomInterne => $data) {
            if (null === $data['capsule']) {
                continue;
            }

            foreach ($data['rubriques'] as $rubriqueData) {
                /** @var Rubrique $rubrique */
                $rubrique = $rubriqueData['rubrique'];
                foreach ($rubriqueData['champs'] as $champData) {
                    $this->processChampOnOdf($odf, $nomInterne, $rubrique, $champData);
                }
            }
        }
    }

    /**
     * Applique les formulaires personnalisés (capsules/rubriques) dans un Segment imbriqué.
     *
     * Accès aux segments tableaux via le magic getter $segment->$segName (pas de mergeSegment).
     *
     * @param array<string, array{capsule: mixed, rubriques: array<int, array{rubrique: Rubrique, champs: array<int, array<string, mixed>>}>}> $capsules
     */
    public function applyFormulairesToSegment(Segment $segment, array $capsules): void
    {
        foreach ($capsules as $nomInterne => $data) {
            if (null === $data['capsule']) {
                continue;
            }

            foreach ($data['rubriques'] as $rubriqueData) {
                /** @var Rubrique $rubrique */
                $rubrique = $rubriqueData['rubrique'];
                foreach ($rubriqueData['champs'] as $champData) {
                    $this->processChampOnSegment($segment, $nomInterne, $rubrique, $champData);
                }
            }
        }
    }

    /**
     * Traite un champ formulaire sur un Odf racine.
     *
     * @param array<string, mixed> $champData
     */
    private function processChampOnOdf(Odf $odf, string $nomInterne, Rubrique $rubrique, array $champData): void
    {
        /** @var Champ $champ */
        $champ = $champData['champ'];
        $typeId = null !== $champ->getType() ? $champ->getType()->getId() : null;

        if (TypeChamp::PARENT === $typeId) {
            if ($champ->getTableau()) {
                $this->processTableauOnOdf($odf, $nomInterne, $rubrique, $champ, $champData);
            } else {
                $this->processParentNonTableau($odf, $nomInterne, $rubrique, $champ, $champData);
            }
        } else {
            $this->processLeafChamp($odf, $nomInterne, $rubrique, $champ, $champData);
        }
    }

    /**
     * Traite un champ formulaire sur un Segment imbriqué.
     *
     * @param array<string, mixed> $champData
     */
    private function processChampOnSegment(Segment $segment, string $nomInterne, Rubrique $rubrique, array $champData): void
    {
        /** @var Champ $champ */
        $champ = $champData['champ'];
        $typeId = null !== $champ->getType() ? $champ->getType()->getId() : null;

        if (TypeChamp::PARENT === $typeId) {
            if ($champ->getTableau()) {
                $this->processTableauOnSegment($segment, $nomInterne, $rubrique, $champ, $champData);
            } else {
                $this->processParentNonTableau($segment, $nomInterne, $rubrique, $champ, $champData);
            }
        } else {
            $this->processLeafChamp($segment, $nomInterne, $rubrique, $champ, $champData);
        }
    }

    /**
     * Traite un champ tableau sur un Odf racine (setSegment + mergeSegment).
     *
     * @param array<string, mixed> $champData
     */
    private function processTableauOnOdf(Odf $odf, string $nomInterne, Rubrique $rubrique, Champ $champ, array $champData): void
    {
        // En-têtes de colonnes
        foreach ($champ->getEnfants() as $enfant) {
            $this->setVariable(
                $odf,
                ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '', (string) ($enfant->getIdx() ?? 0)]),
                $enfant->getNom()
            );
        }

        $segName = ChampNamer::getLoopName($nomInterne.' '.($rubrique->getNom() ?? '').' '.($champ->getNom() ?? '').' valeurs');
        try {
            $segmentTableau = $odf->setSegment($segName);
            $this->fillTableauRows($segmentTableau, $champ, $champData);
            $odf->mergeSegment($segmentTableau);
        } catch (\Exception) {
            // Segment absent du template
        }
    }

    /**
     * Traite un champ tableau sur un Segment imbriqué (magic getter, pas de mergeSegment).
     *
     * @param array<string, mixed> $champData
     */
    private function processTableauOnSegment(Segment $segment, string $nomInterne, Rubrique $rubrique, Champ $champ, array $champData): void
    {
        // En-têtes de colonnes
        foreach ($champ->getEnfants() as $enfant) {
            $this->setVariable(
                $segment,
                ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '', (string) ($enfant->getIdx() ?? 0)]),
                $enfant->getNom()
            );
        }

        $segName = ChampNamer::getLoopName($nomInterne.' '.($rubrique->getNom() ?? '').' '.($champ->getNom() ?? '').' valeurs');
        /** @var Segment|null $segmentTableau */
        $segmentTableau = null;
        try {
            $segmentTableau = $segment->$segName;
        } catch (\Exception) {
            // Segment absent du template
        }

        /** @var Segment|null $segmentTableau */
        if (null !== $segmentTableau) {
            $this->fillTableauRows($segmentTableau, $champ, $champData);
        }
    }

    /**
     * Remplit les lignes d'un segment tableau.
     *
     * @param array<string, mixed> $champData
     */
    private function fillTableauRows(Segment $segmentTableau, Champ $champ, array $champData): void
    {
        /** @var array<int, array<int, Valeur|null>> $lignes */
        $lignes = $champData['lignes'] ?? [];
        if (0 === count($lignes)) {
            foreach ($champ->getEnfants() as $idx => $enfant) {
                $this->setVariable($segmentTableau, sprintf('valeur%d', $idx), '');
            }

            $segmentTableau->merge();
        } else {
            foreach ($lignes as $ligne) {
                foreach ($champ->getEnfants() as $idx => $enfant) {
                    $enfantId = $enfant->getId();
                    /** @var Valeur|null $valeur */
                    $valeur = null !== $enfantId ? ($ligne[$enfantId] ?? null) : null;
                    $val = ValeurResolver::resolve($enfant->getType(), $valeur);
                    $this->setVariable($segmentTableau, sprintf('valeur%d', $idx), $val);
                }

                $segmentTableau->merge();
            }
        }
    }

    /**
     * Traite un champ parent non-tableau (sous-champs directs).
     *
     * @param array<string, mixed> $champData
     */
    private function processParentNonTableau(Odf|Segment $target, string $nomInterne, Rubrique $rubrique, Champ $champ, array $champData): void
    {
        /** @var array<int, array{champ: Champ, valeur: Valeur|null}> $sousChamps */
        $sousChamps = $champData['sous_champs'] ?? [];
        foreach ($sousChamps as $souschampData) {
            /** @var Champ $souschamp */
            $souschamp = $souschampData['champ'];
            /** @var Valeur|null $valeur */
            $valeur = $souschampData['valeur'];
            $val = ValeurResolver::resolve($souschamp->getType(), $valeur);
            $this->setVariable(
                $target,
                ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '', $souschamp->getNom() ?? '']),
                $val
            );
        }
    }

    /**
     * Traite un champ feuille (scalaire simple).
     *
     * @param array<string, mixed> $champData
     */
    private function processLeafChamp(Odf|Segment $target, string $nomInterne, Rubrique $rubrique, Champ $champ, array $champData): void
    {
        /** @var Valeur|null $valeur */
        $valeur = $champData['valeur'] ?? null;
        $val = ValeurResolver::resolve($champ->getType(), $valeur);
        $this->setVariable(
            $target,
            ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '']),
            $val
        );
    }
}
