<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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

            $this->writeDateCell($sheet, 10, $ligne, $row['DATE_DERNIER_AVIS'] ?? null);
            $this->writeDateCell($sheet, 11, $ligne, $row['DATE_PREMIER_AVIS_FAVORABLE'] ?? null);
            $this->writeDateCell($sheet, 12, $ligne, $row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF'] ?? null);

            $sheet->setCellValueByColumnAndRow(13, $ligne, (int) ($row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0) + (int) ($row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
            $sheet->setCellValueByColumnAndRow(14, $ligne, $row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? '');
            $sheet->setCellValueByColumnAndRow(15, $ligne, $row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? '');

            $this->writeDateCell($sheet, 16, $ligne, $row['DATE_DERNIERE_VISITE'] ?? null);

            if ('' !== ($row['DATE_DERNIERE_VISITE'] ?? '') && 0 != ($row['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? 0)) {
                $derniereVisite = \DateTime::createFromFormat('Y-m-d', substr((string) $row['DATE_DERNIERE_VISITE'], 0, 10));
                if ($derniereVisite instanceof \DateTime) {
                    $periodicite = (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS'];
                    $derniereVisite->modify('+'.$periodicite.' months');
                    $sheet->setCellValueByColumnAndRow(17, $ligne, $derniereVisite->format('d/m/Y'));
                }
            }

            $this->writeDateCell($sheet, 18, $ligne, $row['DATE_VISITE_PREVUE'] ?? null);

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

            $this->writeDateCell($sheet, 12, $ligne, $row['DATEINSERT_DOSSIER'] ?? null);

            $sheet->setCellValueByColumnAndRow(13, $ligne, $row['OBJET_DOSSIER'] ?? '');
            $sheet->setCellValueByColumnAndRow(14, $ligne, $row['NUM_DOCURBA'] ?? '');

            $this->writeDateCell($sheet, 15, $ligne, $row['DATEVISITE_DOSSIER'] ?? null);

            $this->writeDateCell($sheet, 16, $ligne, $row['DATECOMM_DOSSIER'] ?? null);

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

    /**
     * Écrit une date au format dd/mm/yyyy dans une cellule à partir d'une valeur brute YYYY-MM-DD.
     * Contourne le bug du writer ODS de PhpSpreadsheet qui n'applique pas les format codes de date
     * (TYPE_NUMERIC écrit toujours office:value-type="float" sans vérifier le format de date).
     *
     * @param string|null $rawDate Valeur brute YYYY-MM-DD (ou YYYY-MM-DD HH:MM:SS)
     */
    private function writeDateCell(Worksheet $sheet, int $col, int $row, ?string $rawDate): void
    {
        if (null === $rawDate || '' === $rawDate) {
            return;
        }

        $date = \DateTime::createFromFormat('Y-m-d', substr($rawDate, 0, 10));
        if ($date instanceof \DateTime) {
            $sheet->setCellValueByColumnAndRow($col, $row, $date->format('d/m/Y'));
        }
    }
}
