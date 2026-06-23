<?php

namespace App\Service\Rapport;

use App\Constant\TypePrescription;
use App\Entity\PrescriptionDossier;

/**
 * Helpers statiques de manipulation des tableaux de prescriptions pour la génération ODT.
 *
 * Extrait de DossierRapportOdtService pour permettre les tests unitaires sans infrastructure.
 */
final class PrescriptionArrayHelper
{
    private function __construct()
    {
    }

    /**
     * Supprime les prescriptions ayant une date de levée (DATE_LEVEE non vide).
     *
     * Port direct legacy Service_Dossier::withoutLevees().
     *
     * @param array<int, array<int, array<string, mixed>>> $prescArray
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function withoutLevees(array $prescArray): array
    {
        $result = [];
        foreach ($prescArray as $i => $prescriptions) {
            foreach ($prescriptions as $j => $assoc) {
                if ('' === ($assoc['DATE_LEVEE'] ?? '')) {
                    $result[$i][$j] = $assoc;
                }
            }
        }

        return $result;
    }

    /**
     * Supprime les entrées sans reprise (ID_DOSSIER_REPRISE vide = actuelles seules).
     *
     * @param array<int, array<int, array<string, mixed>>> $prescArray
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function withoutActuals(array $prescArray): array
    {
        $result = [];
        foreach ($prescArray as $i => $prescriptions) {
            foreach ($prescriptions as $j => $assoc) {
                if (!empty($assoc['ID_DOSSIER_REPRISE'])) {
                    $result[$i][$j] = $assoc;
                }
            }
        }

        return $result;
    }

    /**
     * Supprime les entrées issues d'une reprise (ID_DOSSIER_REPRISE non vide).
     *
     * @param array<int, array<int, array<string, mixed>>> $prescArray
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function withoutPrevious(array $prescArray): array
    {
        $result = [];
        foreach ($prescArray as $i => $prescriptions) {
            foreach ($prescriptions as $j => $assoc) {
                if (empty($assoc['ID_DOSSIER_REPRISE'])) {
                    $result[$i][$j] = $assoc;
                }
            }
        }

        return $result;
    }

    /**
     * Construit la chaîne "texte - article" pour une prescription.
     *
     * Port direct du bloc partagé entre addPrescription() et addPrescriptionCC()
     * (creationdoc.phtml).
     *
     * @param array<int, array<string, mixed>> $prescriptions Lignes d'une même prescription
     */
    public static function buildTextearticle(array $prescriptions): string
    {
        $nbCpt = count($prescriptions);
        $textearticle = '';
        foreach ($prescriptions as $assoc) {
            /** @var array<string, string|null> $assoc */
            $texteT = (string) ($assoc['LIBELLE_TEXTE'] ?? '');
            $articleT = (string) ($assoc['LIBELLE_ARTICLE'] ?? '');
            $textearticle .= ('' !== $texteT ? $texteT.' - ' : ' - ');
            if ($nbCpt > 1) {
                $textearticle .= ('' !== $articleT ? $articleT.', ' : ', ');
            } else {
                $textearticle .= ('' !== $articleT ? $articleT.' ' : ' ');
            }

            --$nbCpt;
        }

        return $textearticle;
    }

    /**
     * Retourne le nom du segment ODT pour un type de prescription et un contenu donné.
     *
     * @param int    $type        TypePrescription::*
     * @param string $typeContenu 'all' | 'reprises' | 'actuelles'
     */
    public static function getPrescriptionSegmentName(int $type, string $typeContenu): string
    {
        $segNames = [
            TypePrescription::REGLEMENTAIRE => [
                'all' => 'listeReg',
                'reprises' => 'listeRegReprises',
                'actuelles' => 'listeRegActuelles',
            ],
            TypePrescription::EXPLOITATION => [
                'all' => 'listeExploitation',
                'reprises' => 'listeExploitationReprises',
                'actuelles' => 'listeExploitationActuelles',
            ],
            TypePrescription::AMELIORATION => [
                'all' => 'listeAmelioration',
                'reprises' => 'listeAmeliorationReprises',
                'actuelles' => 'listeAmeliorationActuelles',
            ],
        ];

        return $segNames[$type][$typeContenu] ?? '';
    }

    /**
     * Convertit un tableau de PrescriptionDossier en tableau imbriqué pour la génération ODT.
     *
     * Port direct de DossierRapportOdtService::buildPrescriptionNestedArray().
     *
     * @param PrescriptionDossier[] $prescriptions
     *
     * @return array<int, array<int, array<string, string>>>
     */
    public static function buildNestedArray(array $prescriptions): array
    {
        /** @var array<int, array<int, array<string, string>>> $result */
        $result = [];
        foreach ($prescriptions as $i => $p) {
            foreach ($p->getEffectiveAssociations() as $j => $assoc) {
                $prescType = $p->getPrescriptionType();
                $dateLevee = $p->getDateLevee();
                $reprise = $p->getDossierReprise();
                $repriseId = (null !== $reprise && null !== $reprise->getId()) ? (string) $reprise->getId() : '';
                $result[$i][$j] = [
                    'NUM_PRESCRIPTION_DOSSIER' => null !== $p->getNumero() ? (string) $p->getNumero() : '',
                    'LIBELLE_PRESCRIPTION_DOSSIER' => $p->getLibelle() ?? '',
                    'PRESCRIPTIONTYPE_LIBELLE' => null !== $prescType ? ($prescType->getLibelle() ?? '') : '',
                    'LIBELLE_TEXTE' => null !== $assoc->getTexte() ? ($assoc->getTexte()->getLibelle() ?? '') : '',
                    'LIBELLE_ARTICLE' => null !== $assoc->getArticle() ? ($assoc->getArticle()->getLibelle() ?? '') : '',
                    'DATE_LEVEE' => null !== $dateLevee ? $dateLevee->format('d/m/Y') : '',
                    'ID_DOSSIER_REPRISE' => $repriseId,
                ];
            }
        }

        /** @var array<int, array<int, array<string, string>>> $typedResult */
        $typedResult = $result;

        return $typedResult;
    }
}
