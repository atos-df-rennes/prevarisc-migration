<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
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
     * @param array<int, array<string, mixed>> $rows
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
        $sheet->getStyle('A1:Y1')->applyFromArray($styleArray);
        $sheet->getStyle('A1:Y1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:Y1')->getFont()->setSize(11)->setBold(true);

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
        $sheet->setCellValueByColumnAndRow(25, 1, 'Facteur de criticité');

        $ligne = 2;
        foreach ($rows as $row) {
            $this->writeTextCell($sheet, 1, $ligne, $row['LIBELLE_COMMUNE'] ?? '');
            $this->writeTextCell($sheet, 2, $ligne, $row['LIBELLE_CATEGORIE'] ?? '');
            $this->writeTextCell($sheet, 3, $ligne, $row['LIBELLE_TYPE'] ?? '');
            $this->writeTextCell($sheet, 4, $ligne, $row['LIBELLE_ACTIVITE'] ?? '');
            $this->writeTextCell($sheet, 5, $ligne, $row['LIBELLE_COMMISSION'] ?? '');
            $this->writeTextCell($sheet, 6, $ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
            $this->writeTextCell($sheet, 7, $ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
            $this->writeTextCell($sheet, 8, $ligne, $row['LIBELLE_STATUT'] ?? '');
            $this->writeTextCell($sheet, 9, $ligne, $row['LIBELLE_AVIS'] ?? '');

            $this->writeDateCell($sheet, 10, $ligne, $row['DATE_DERNIER_AVIS'] ?? null);
            $this->writeDateCell($sheet, 11, $ligne, $row['DATE_PREMIER_AVIS_FAVORABLE'] ?? null);
            $this->writeDateCell($sheet, 12, $ligne, $row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF'] ?? null);

            $effectifPublic = is_numeric($row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? null) ? (int) $row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] : 0;
            $effectifPersonnel = is_numeric($row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? null) ? (int) $row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] : 0;
            $sheet->setCellValueByColumnAndRow(13, $ligne, $effectifPublic + $effectifPersonnel);
            $sheet->setCellValueByColumnAndRow(14, $ligne, $effectifPublic);
            $sheet->setCellValueByColumnAndRow(15, $ligne, $effectifPersonnel);

            $this->writeDateCell($sheet, 16, $ligne, $row['DATE_DERNIERE_VISITE'] ?? null);

            if (is_string($row['DATE_DERNIERE_VISITE'] ?? null) && '' !== $row['DATE_DERNIERE_VISITE']
                && is_numeric($row['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? null)
                && 0 !== (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS']
            ) {
                $derniereVisite = \DateTime::createFromFormat('Y-m-d', substr($row['DATE_DERNIERE_VISITE'], 0, 10));
                if ($derniereVisite instanceof \DateTime) {
                    $periodicite = (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS'];
                    $derniereVisite->modify('+'.$periodicite.' months');
                    $this->writeTextCell($sheet, 17, $ligne, $derniereVisite->format('d/m/Y'));
                }
            }

            $this->writeDateCell($sheet, 18, $ligne, $row['DATE_VISITE_PREVUE'] ?? null);

            $this->writeTextCell($sheet, 19, $ligne, trim($this->toStr($row['NUMERO_ADRESSE'] ?? null).' '.$this->toStr($row['LIBELLE_RUE'] ?? null).' '.$this->toStr($row['COMPLEMENT_ADRESSE'] ?? null).' '.$this->toStr($row['CODEPOSTAL_COMMUNE'] ?? null)));
            $this->writeTextCell($sheet, 20, $ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
            $this->writeTextCell($sheet, 21, $ligne, $row['LIBELLE_ETABLISSEMENT_PERE'] ?? '');
            $this->writeTextCell($sheet, 22, $ligne, $row['LIBELLE_GENRE'] ?? '');
            $this->writeTextCell($sheet, 23, $ligne, trim($this->toStr($row['PRENOM_UTILISATEURINFORMATIONS'] ?? null).' '.$this->toStr($row['NOM_UTILISATEURINFORMATIONS'] ?? null)));
            $sheet->setCellValueByColumnAndRow(24, $ligne, ($row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ?? false) ? 'oui' : 'non');
            $this->writeTextCell($sheet, 25, $ligne, $row['FACTDANGE_DOSSIER'] ?? '');

            ++$ligne;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save('php://output');
    }

    /**
     * Exporte une liste de dossiers au format ODS sur php://output.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string|null                      $provenance Filtre de provenance (ex: '1' pour PLATAU)
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
            $this->writeTextCell($sheet, 1, $ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
            $this->writeTextCell($sheet, 2, $ligne, $row['LIBELLE_COMMUNE'] ?? '');
            $this->writeTextCell($sheet, 3, $ligne, $row['LIBELLE_CATEGORIE'] ?? '');
            $this->writeTextCell($sheet, 4, $ligne, $row['LIBELLE_TYPE_ETABLISSEMENT'] ?? '');
            $this->writeTextCell($sheet, 5, $ligne, $row['LIBELLE_ACTIVITE'] ?? '');
            $this->writeTextCell($sheet, 6, $ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
            $this->writeTextCell($sheet, 7, $ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
            $this->writeTextCell($sheet, 8, $ligne, $row['LIBELLE_STATUT'] ?? '');
            $this->writeTextCell($sheet, 9, $ligne, $row['LIBELLE_GENRE'] ?? '');
            $this->writeTextCell($sheet, 10, $ligne, $row['LIBELLE_DOSSIERTYPE'] ?? '');
            $this->writeTextCell($sheet, 11, $ligne, $row['LIBELLE_DOSSIERNATURE'] ?? '');

            $this->writeDateCell($sheet, 12, $ligne, $row['DATEINSERT_DOSSIER'] ?? null);

            $this->writeTextCell($sheet, 13, $ligne, $row['OBJET_DOSSIER'] ?? '');
            $this->writeTextCell($sheet, 14, $ligne, $row['NUM_DOCURBA'] ?? '');

            $this->writeDateCell($sheet, 15, $ligne, $row['DATEVISITE_DOSSIER'] ?? null);

            $this->writeDateCell($sheet, 16, $ligne, $row['DATECOMM_DOSSIER'] ?? null);

            $this->writeTextCell($sheet, 17, $ligne, $row['LIBELLE_COMMISSION'] ?? '');
            $this->writeTextCell($sheet, 18, $ligne, $row['LIBELLE_AVIS_RAPPORTEUR'] ?? '');
            $this->writeTextCell($sheet, 19, $ligne, $row['LIBELLE_AVIS_COMMISSION'] ?? '');
            $this->writeTextCell($sheet, 20, $ligne, trim($this->toStr($row['PRENOM_UTILISATEURINFORMATIONS'] ?? null).' '.$this->toStr($row['NOM_UTILISATEURINFORMATIONS'] ?? null)));

            $sheet->setCellValueByColumnAndRow(21, $ligne, '' !== ($row['ID_PIECEJOINTE'] ?? '') ? 'Oui' : 'Non');

            if ('1' === $provenance) {
                $this->writeTextCell($sheet, 22, $ligne, $row['ID_PLATAU'] ?? '');
            }

            ++$ligne;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save('php://output');
    }

    /**
     * Convertit une valeur mixed en string de façon sûre pour PHPStan level 10.
     *
     * @param mixed $value
     */
    private function toStr($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Écrit une valeur texte dans une cellule en forçant le type STRING.
     * Empêche l'interprétation comme formule (spreadsheet formula injection)
     * pour les valeurs commençant par =, +, -, @.
     *
     * @param mixed $value
     */
    private function writeTextCell(Worksheet $sheet, int $col, int $row, $value): void
    {
        $sheet->setCellValueExplicitByColumnAndRow($col, $row, is_scalar($value) ? (string) $value : '', DataType::TYPE_STRING);
    }

    /**
     * Écrit une date au format dd/mm/yyyy dans une cellule à partir d'une valeur brute YYYY-MM-DD.
     * Contourne le bug du writer ODS de PhpSpreadsheet qui n'applique pas les format codes de date
     * (TYPE_NUMERIC écrit toujours office:value-type="float" sans vérifier le format de date).
     *
     * @param mixed $rawDate Valeur brute YYYY-MM-DD (ou YYYY-MM-DD HH:MM:SS)
     */
    private function writeDateCell(Worksheet $sheet, int $col, int $row, $rawDate): void
    {
        if (!is_string($rawDate) || '' === $rawDate) {
            return;
        }

        $date = \DateTime::createFromFormat('Y-m-d', substr($rawDate, 0, 10));
        if ($date instanceof \DateTime) {
            $this->writeTextCell($sheet, $col, $row, $date->format('d/m/Y'));
        }
    }
}
