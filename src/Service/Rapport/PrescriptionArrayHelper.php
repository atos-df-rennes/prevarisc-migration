<?php

namespace App\Service\Rapport;

use App\Constant\TypePrescription;

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
}
