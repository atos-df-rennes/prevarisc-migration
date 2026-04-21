<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Service d'export ODS (Calc) pour les recherches d'établissements et de dossiers.
 * Encapsule la construction du Spreadsheet PhpSpreadsheet et écrit sur php://output.
 */
class SearchExport
{
    /**
     * Exporte une liste d'établissements au format ODS sur php://output.
     *
     * @param array<int, array<string, string|null>> $rows
     */
    public function exportEtablissements(array $rows): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Liste des établissements');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->setBold(false);
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $styleArray = [
            'borders' => ['allBorders' => ['style' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:X1')->applyFromArray($styleArray);
        $sheet->getStyle('A1:X1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:X1')->getFont()->setSize(11)->setBold(true);

        $sheet->setCellValueByColumnAndRow(1, 1, 'Commune');
        $sheet->setCellValueByColumnAndRow(2, 1, 'Catégorie');
        $sheet->setCellValueByColumnAndRow(3, 1, 'Type');
        $sheet->setCellValueByColumnAndRow(4, 1, 'Activité');
        $sheet->setCellValueByColumnAndRow(5, 1, 'Commission compétente');
        $sheet->setCellValueByColumnAndRow(6, 1, 'Code/identifiant établissement');
        $sheet->setCellValueByColumnAndRow(7, 1, 'Libellé établissement');
        $sheet->setCellValueByColumnAndRow(8, 1, 'Statut');
        $sheet->setCellValueByColumnAndRow(9, 1, 'Avis');
        $sheet->setCellValueByColumnAndRow(10, 1, 'Date du dernier avis');
        $sheet->setCellValueByColumnAndRow(11, 1, 'Date du premier avis favorable');
        $sheet->setCellValueByColumnAndRow(12, 1, 'Date du premier avis défavorable consécutif');
        $sheet->setCellValueByColumnAndRow(13, 1, 'Effectif total');
        $sheet->setCellValueByColumnAndRow(14, 1, 'Effectif public');
        $sheet->setCellValueByColumnAndRow(15, 1, 'Effectif personnel');
        $sheet->setCellValueByColumnAndRow(16, 1, 'Date de dernière visite');
        $sheet->setCellValueByColumnAndRow(17, 1, 'Date de prochaine visite périodique');
        $sheet->setCellValueByColumnAndRow(18, 1, 'Date de visite prévue');
        $sheet->setCellValueByColumnAndRow(19, 1, 'Adresse');
        $sheet->setCellValueByColumnAndRow(20, 1, 'Groupement territorial compétent');
        $sheet->setCellValueByColumnAndRow(21, 1, 'Libellé du père/site');
        $sheet->setCellValueByColumnAndRow(22, 1, 'Genre');
        $sheet->setCellValueByColumnAndRow(23, 1, 'Préventionniste');
        $sheet->setCellValueByColumnAndRow(24, 1, 'Présence de locaux à sommeil');

        $ligne = 2;
        foreach ($rows as $row) {
            $sheet->setCellValueByColumnAndRow(1, $ligne, $row['LIBELLE_COMMUNE'] ?? '');
            $sheet->setCellValueByColumnAndRow(2, $ligne, $row['LIBELLE_CATEGORIE'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $ligne, $row['LIBELLE_TYPE'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $ligne, $row['LIBELLE_ACTIVITE'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $ligne, $row['LIBELLE_COMMISSION'] ?? '');
            $sheet->setCellValueByColumnAndRow(6, $ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
            $sheet->setCellValueByColumnAndRow(7, $ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
            $sheet->setCellValueByColumnAndRow(8, $ligne, $row['LIBELLE_STATUT'] ?? '');
            $sheet->setCellValueByColumnAndRow(9, $ligne, $row['LIBELLE_AVIS'] ?? '');

            if ('' !== ($row['DATE_DERNIER_AVIS'] ?? '')) {
                $dateDernierAvis = preg_split('/-|\//', (string) $row['DATE_DERNIER_AVIS']);
                if (is_array($dateDernierAvis) && isset($dateDernierAvis[0], $dateDernierAvis[1], $dateDernierAvis[2])) {
                    if (!is_numeric($dateDernierAvis[2])) {
                        $dateDernierAvis[2] = substr($dateDernierAvis[2], 0, 2);
                    }

                    $datetimeDernierAvis = Date::formattedPHPToExcel((int) $dateDernierAvis[0], (int) $dateDernierAvis[1], (int) $dateDernierAvis[2]);
                    $sheet->setCellValueByColumnAndRow(10, $ligne, $datetimeDernierAvis);
                    $sheet->getStyleByColumnAndRow(10, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            if ('' !== ($row['DATE_PREMIER_AVIS_FAVORABLE'] ?? '')) {
                $datePremierAvisFav = preg_split('/-|\//', (string) $row['DATE_PREMIER_AVIS_FAVORABLE']);
                if (is_array($datePremierAvisFav) && isset($datePremierAvisFav[0], $datePremierAvisFav[1], $datePremierAvisFav[2])) {
                    if (!is_numeric($datePremierAvisFav[2])) {
                        $datePremierAvisFav[2] = substr($datePremierAvisFav[2], 0, 2);
                    }

                    $dtPremierAvisFav = Date::formattedPHPToExcel((int) $datePremierAvisFav[0], (int) $datePremierAvisFav[1], (int) $datePremierAvisFav[2]);
                    $sheet->setCellValueByColumnAndRow(11, $ligne, $dtPremierAvisFav);
                    $sheet->getStyleByColumnAndRow(11, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            if ('' !== ($row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF'] ?? '')) {
                $datePremierAvisDefCons = preg_split('/-|\//', (string) $row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF']);
                if (is_array($datePremierAvisDefCons) && isset($datePremierAvisDefCons[0], $datePremierAvisDefCons[1], $datePremierAvisDefCons[2])) {
                    if (!is_numeric($datePremierAvisDefCons[2])) {
                        $datePremierAvisDefCons[2] = substr($datePremierAvisDefCons[2], 0, 2);
                    }

                    $dtDefCons = Date::formattedPHPToExcel((int) $datePremierAvisDefCons[0], (int) $datePremierAvisDefCons[1], (int) $datePremierAvisDefCons[2]);
                    $sheet->setCellValueByColumnAndRow(12, $ligne, $dtDefCons);
                    $sheet->getStyleByColumnAndRow(12, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            $sheet->setCellValueByColumnAndRow(13, $ligne, (int) ($row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0) + (int) ($row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
            $sheet->setCellValueByColumnAndRow(14, $ligne, $row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? '');
            $sheet->setCellValueByColumnAndRow(15, $ligne, $row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? '');

            if ('' !== ($row['DATE_DERNIERE_VISITE'] ?? '')) {
                $dateDerniereVisite = preg_split('/-|\//', (string) $row['DATE_DERNIERE_VISITE']);
                if (is_array($dateDerniereVisite) && isset($dateDerniereVisite[0], $dateDerniereVisite[1], $dateDerniereVisite[2])) {
                    if (!is_numeric($dateDerniereVisite[2])) {
                        $dateDerniereVisite[2] = substr($dateDerniereVisite[2], 0, 2);
                    }

                    $datetimeDerniereVisite = Date::formattedPHPToExcel((int) $dateDerniereVisite[0], (int) $dateDerniereVisite[1], (int) $dateDerniereVisite[2]);
                    $sheet->setCellValueByColumnAndRow(16, $ligne, $datetimeDerniereVisite);
                    $sheet->getStyleByColumnAndRow(16, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

                    if (0 != $row['PERIODICITE_ETABLISSEMENTINFORMATIONS']) {
                        $periodicite = (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS'];
                        $tsDateVisite = strtotime((string) ($row['DATE_DERNIERE_VISITE'] ?? ''));
                        if (false !== $tsDateVisite) {
                            $tsProchaine = strtotime('+'.$periodicite.' months', $tsDateVisite);
                            if (false !== $tsProchaine) {
                                $dateProchaineVisite = date('Y-m-j', $tsProchaine);
                                $dateProchaineVisiteParts = preg_split('/-|\//', $dateProchaineVisite);
                                if (is_array($dateProchaineVisiteParts) && count($dateProchaineVisiteParts) >= 3) {
                                    $datetimeProchaineVisite = Date::formattedPHPToExcel((int) $dateProchaineVisiteParts[0], (int) $dateProchaineVisiteParts[1], (int) $dateProchaineVisiteParts[2]);
                                    $sheet->setCellValueByColumnAndRow(17, $ligne, $datetimeProchaineVisite);
                                    $sheet->getStyleByColumnAndRow(17, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                                }
                            }
                        }
                    }
                }
            }

            if ('' !== ($row['DATE_VISITE_PREVUE'] ?? '')) {
                $dateVisitePrevue = preg_split('/-|\//', (string) $row['DATE_VISITE_PREVUE']);
                if (is_array($dateVisitePrevue) && isset($dateVisitePrevue[0], $dateVisitePrevue[1], $dateVisitePrevue[2])) {
                    if (!is_numeric($dateVisitePrevue[2])) {
                        $dateVisitePrevue[2] = substr($dateVisitePrevue[2], 0, 2);
                    }

                    $datetimeVisitePrevue = Date::formattedPHPToExcel((int) $dateVisitePrevue[0], (int) $dateVisitePrevue[1], (int) $dateVisitePrevue[2]);
                    $sheet->setCellValueByColumnAndRow(18, $ligne, $datetimeVisitePrevue);
                    $sheet->getStyleByColumnAndRow(18, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            $sheet->setCellValueByColumnAndRow(19, $ligne, ($row['NUMERO_ADRESSE'] ?? '').' '.($row['LIBELLE_RUE'] ?? '').' '.($row['COMPLEMENT_ADRESSE'] ?? '').' '.($row['CODEPOSTAL_COMMUNE'] ?? ''));
            $sheet->setCellValueByColumnAndRow(20, $ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
            $sheet->setCellValueByColumnAndRow(21, $ligne, $row['LIBELLE_ETABLISSEMENT_PERE'] ?? '');
            $sheet->setCellValueByColumnAndRow(22, $ligne, $row['LIBELLE_GENRE'] ?? '');
            $sheet->setCellValueByColumnAndRow(23, $ligne, ($row['PRENOM_UTILISATEURINFORMATIONS'] ?? '').' '.($row['NOM_UTILISATEURINFORMATIONS'] ?? ''));
            $sheet->setCellValueByColumnAndRow(24, $ligne, ($row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ?? false) ? 'oui' : 'non');

            ++$ligne;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save('php://output');
    }

    /**
     * Exporte une liste de dossiers au format ODS sur php://output.
     *
     * @param array<int, array<string, string|null>> $rows
     * @param string|null                            $provenance Filtre de provenance (ex: '1' pour PLATAU)
     */
    public function exportDossiers(array $rows, ?string $provenance): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Liste des dossiers');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->setBold(false);
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $styleArray = [
            'borders' => ['allBorders' => ['style' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:V1')->applyFromArray($styleArray);
        $sheet->getStyle('A1:V1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:V1')->getFont()->setSize(11)->setBold(true);

        $sheet->setCellValueByColumnAndRow(1, 1, 'Groupement');
        $sheet->setCellValueByColumnAndRow(2, 1, 'Commune');
        $sheet->setCellValueByColumnAndRow(3, 1, 'Catégorie');
        $sheet->setCellValueByColumnAndRow(4, 1, 'Type');
        $sheet->setCellValueByColumnAndRow(5, 1, 'Activité');
        $sheet->setCellValueByColumnAndRow(6, 1, 'Code/identifiant établissement');
        $sheet->setCellValueByColumnAndRow(7, 1, 'Libellé établissement');
        $sheet->setCellValueByColumnAndRow(8, 1, 'Statut');
        $sheet->setCellValueByColumnAndRow(9, 1, 'Genre');
        $sheet->setCellValueByColumnAndRow(10, 1, 'Type du dossier');
        $sheet->setCellValueByColumnAndRow(11, 1, 'Nature du dossier');
        $sheet->setCellValueByColumnAndRow(12, 1, 'Date de création du dossier');
        $sheet->setCellValueByColumnAndRow(13, 1, 'Objet du dossier');
        $sheet->setCellValueByColumnAndRow(14, 1, 'Numéro document urbanisme');
        $sheet->setCellValueByColumnAndRow(15, 1, 'Date de visite');
        $sheet->setCellValueByColumnAndRow(16, 1, 'Date de la commission en salle');
        $sheet->setCellValueByColumnAndRow(17, 1, 'Commission du dossier');
        $sheet->setCellValueByColumnAndRow(18, 1, 'Avis rapporteur');
        $sheet->setCellValueByColumnAndRow(19, 1, 'Avis commission');
        $sheet->setCellValueByColumnAndRow(20, 1, 'Préventionniste en charge du dossier');
        $sheet->setCellValueByColumnAndRow(21, 1, 'Pièces jointes ?');
        $sheet->setCellValueByColumnAndRow(22, 1, 'Identifiant PLATAU');

        $ligne = 2;
        foreach ($rows as $row) {
            $sheet->setCellValueByColumnAndRow(1, $ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
            $sheet->setCellValueByColumnAndRow(2, $ligne, $row['LIBELLE_COMMUNE'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $ligne, $row['LIBELLE_CATEGORIE'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $ligne, $row['LIBELLE_TYPE_ETABLISSEMENT'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $ligne, $row['LIBELLE_ACTIVITE'] ?? '');
            $sheet->setCellValueByColumnAndRow(6, $ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
            $sheet->setCellValueByColumnAndRow(7, $ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
            $sheet->setCellValueByColumnAndRow(8, $ligne, $row['LIBELLE_STATUT'] ?? '');
            $sheet->setCellValueByColumnAndRow(9, $ligne, $row['LIBELLE_GENRE'] ?? '');
            $sheet->setCellValueByColumnAndRow(10, $ligne, $row['LIBELLE_DOSSIERTYPE'] ?? '');
            $sheet->setCellValueByColumnAndRow(11, $ligne, $row['LIBELLE_DOSSIERNATURE'] ?? '');

            if ('' !== ($row['DATEINSERT_DOSSIER'] ?? '')) {
                $dateCreationDossier = preg_split('/-|\//', (string) $row['DATEINSERT_DOSSIER']);
                if (is_array($dateCreationDossier) && isset($dateCreationDossier[0], $dateCreationDossier[1], $dateCreationDossier[2])) {
                    if (!is_numeric($dateCreationDossier[2])) {
                        $dateCreationDossier[2] = substr($dateCreationDossier[2], 0, 2);
                    }

                    $datetimeCreationDossier = Date::formattedPHPToExcel((int) $dateCreationDossier[0], (int) $dateCreationDossier[1], (int) $dateCreationDossier[2]);
                    $sheet->setCellValueByColumnAndRow(12, $ligne, $datetimeCreationDossier);
                    $sheet->getStyleByColumnAndRow(12, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            $sheet->setCellValueByColumnAndRow(13, $ligne, $row['OBJET_DOSSIER'] ?? '');
            $sheet->setCellValueByColumnAndRow(14, $ligne, $row['NUM_DOCURBA'] ?? '');

            if ('' !== ($row['DATEVISITE_DOSSIER'] ?? '')) {
                $dateVisiteDossier = preg_split('/-|\//', (string) $row['DATEVISITE_DOSSIER']);
                if (is_array($dateVisiteDossier) && isset($dateVisiteDossier[0], $dateVisiteDossier[1], $dateVisiteDossier[2])) {
                    if (!is_numeric($dateVisiteDossier[2])) {
                        $dateVisiteDossier[2] = substr($dateVisiteDossier[2], 0, 2);
                    }

                    $datetimeVisiteDossier = Date::formattedPHPToExcel((int) $dateVisiteDossier[0], (int) $dateVisiteDossier[1], (int) $dateVisiteDossier[2]);
                    $sheet->setCellValueByColumnAndRow(15, $ligne, $datetimeVisiteDossier);
                    $sheet->getStyleByColumnAndRow(15, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            if ('' !== ($row['DATECOMM_DOSSIER'] ?? '')) {
                $dateCommissionDossier = preg_split('/-|\//', (string) $row['DATECOMM_DOSSIER']);
                if (is_array($dateCommissionDossier) && isset($dateCommissionDossier[0], $dateCommissionDossier[1], $dateCommissionDossier[2])) {
                    if (!is_numeric($dateCommissionDossier[2])) {
                        $dateCommissionDossier[2] = substr($dateCommissionDossier[2], 0, 2);
                    }

                    $datetimeCommissionDossier = Date::formattedPHPToExcel((int) $dateCommissionDossier[0], (int) $dateCommissionDossier[1], (int) $dateCommissionDossier[2]);
                    $sheet->setCellValueByColumnAndRow(16, $ligne, $datetimeCommissionDossier);
                    $sheet->getStyleByColumnAndRow(16, $ligne)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            }

            $sheet->setCellValueByColumnAndRow(17, $ligne, $row['LIBELLE_COMMISSION'] ?? '');
            $sheet->setCellValueByColumnAndRow(18, $ligne, $row['LIBELLE_AVIS_RAPPORTEUR'] ?? '');
            $sheet->setCellValueByColumnAndRow(19, $ligne, $row['LIBELLE_AVIS_COMMISSION'] ?? '');
            $sheet->setCellValueByColumnAndRow(20, $ligne, ($row['PRENOM_UTILISATEURINFORMATIONS'] ?? '').' '.($row['NOM_UTILISATEURINFORMATIONS'] ?? ''));

            $sheet->setCellValueByColumnAndRow(21, $ligne, '' !== ($row['ID_PIECEJOINTE'] ?? '') ? 'Oui' : 'Non');

            if ('1' === $provenance) {
                $sheet->setCellValueByColumnAndRow(22, $ligne, $row['ID_PLATAU'] ?? '');
            }

            ++$ligne;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save('php://output');
    }
}
