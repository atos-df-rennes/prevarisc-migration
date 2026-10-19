<?php

namespace App\Service;

use App\Constant\Avis;
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
final class SearchExport
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
        $sheet->getStyle('A1:Z1')->applyFromArray($styleArray);
        $sheet->getStyle('A1:Z1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:Z1')->getFont()->setSize(11)->setBold(true);

        $sheet->setCellValue('A1', 'Commune');
        $sheet->setCellValue('B1', 'Catégorie');
        $sheet->setCellValue('C1', 'Type');
        $sheet->setCellValue('D1', 'Activité');
        $sheet->setCellValue('E1', 'Commission compétente');
        $sheet->setCellValue('F1', 'Code/identifiant établissement');
        $sheet->setCellValue('G1', 'Libellé établissement');
        $sheet->setCellValue('H1', 'Statut');
        $sheet->setCellValue('I1', 'Avis');
        $sheet->setCellValue('J1', 'Date du dernier avis');
        $sheet->setCellValue('K1', 'Date du premier avis favorable');
        $sheet->setCellValue('L1', 'Date du premier avis défavorable consécutif');
        $sheet->setCellValue('M1', 'Effectif total');
        $sheet->setCellValue('N1', 'Effectif public');
        $sheet->setCellValue('O1', 'Effectif personnel');
        $sheet->setCellValue('P1', 'Date de dernière visite');
        $sheet->setCellValue('Q1', 'Date de prochaine visite périodique');
        $sheet->setCellValue('R1', 'Date de visite prévue');
        $sheet->setCellValue('S1', 'Adresse');
        $sheet->setCellValue('T1', 'Libellé du père/site');
        $sheet->setCellValue('U1', 'Genre');
        $sheet->setCellValue('V1', 'Préventionniste');
        $sheet->setCellValue('W1', 'Présence de locaux à sommeil');
        $sheet->setCellValue('X1', 'Facteur de criticité');
        $sheet->setCellValue('Y1', 'Groupement(s)');

        $ligne = 2;
        foreach ($rows as $row) {
            $this->writeTextCell($sheet, 'A'.$ligne, $row['LIBELLE_COMMUNE'] ?? '');
            $this->writeTextCell($sheet, 'B'.$ligne, $row['LIBELLE_CATEGORIE'] ?? '');
            $this->writeTextCell($sheet, 'C'.$ligne, $row['LIBELLE_TYPE'] ?? '');
            $this->writeTextCell($sheet, 'D'.$ligne, $row['LIBELLE_ACTIVITE'] ?? '');
            $this->writeTextCell($sheet, 'E'.$ligne, $row['LIBELLE_COMMISSION'] ?? '');
            $this->writeTextCell($sheet, 'F'.$ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
            $this->writeTextCell($sheet, 'G'.$ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
            $this->writeTextCell($sheet, 'H'.$ligne, $row['LIBELLE_STATUT'] ?? '');
            $this->writeTextCell($sheet, 'I'.$ligne, $row['LIBELLE_AVIS'] ?? '');

            $this->writeDateCell($sheet, 'J'.$ligne, $row['DATE_DERNIER_AVIS'] ?? null);
            $this->writeDateCell($sheet, 'K'.$ligne, $row['DATE_PREMIER_AVIS_FAVORABLE'] ?? null);
            $this->writeDateCell($sheet, 'L'.$ligne, $row['DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF'] ?? null);

            $effectifPublic = is_numeric($row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? null) ? (int) $row['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] : 0;
            $effectifPersonnel = is_numeric($row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? null) ? (int) $row['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] : 0;
            $sheet->setCellValue('M'.$ligne, $effectifPublic + $effectifPersonnel);
            $sheet->setCellValue('N'.$ligne, $effectifPublic);
            $sheet->setCellValue('O'.$ligne, $effectifPersonnel);

            $this->writeDateCell($sheet, 'P'.$ligne, $row['DATE_DERNIERE_VISITE'] ?? null);

            if (is_string($row['DATE_DERNIERE_VISITE'] ?? null) && '' !== $row['DATE_DERNIERE_VISITE']
                && is_numeric($row['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? null)
                && 0 !== (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS']
            ) {
                $derniereVisite = \DateTime::createFromFormat('Y-m-d', substr($row['DATE_DERNIERE_VISITE'], 0, 10));
                if ($derniereVisite instanceof \DateTime) {
                    $periodicite = (int) $row['PERIODICITE_ETABLISSEMENTINFORMATIONS'];
                    $derniereVisite->modify('+'.$periodicite.' months');
                    $this->writeTextCell($sheet, 'Q'.$ligne, $derniereVisite->format('d/m/Y'));
                }
            }

            $this->writeDateCell($sheet, 'R'.$ligne, $row['DATE_VISITE_PREVUE'] ?? null);

            $this->writeTextCell($sheet, 'S'.$ligne, trim($this->toStr($row['NUMERO_ADRESSE'] ?? null).' '.$this->toStr($row['LIBELLE_RUE'] ?? null).' '.$this->toStr($row['COMPLEMENT_ADRESSE'] ?? null).' '.$this->toStr($row['CODEPOSTAL_COMMUNE'] ?? null)));
            $this->writeTextCell($sheet, 'T'.$ligne, $row['LIBELLE_ETABLISSEMENT_PERE'] ?? '');
            $this->writeTextCell($sheet, 'U'.$ligne, $row['LIBELLE_GENRE'] ?? '');
            $this->writeTextCell($sheet, 'V'.$ligne, trim($this->toStr($row['PRENOM_UTILISATEURINFORMATIONS'] ?? null).' '.$this->toStr($row['NOM_UTILISATEURINFORMATIONS'] ?? null)));
            $sheet->setCellValue('W'.$ligne, ($row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ?? false) ? 'oui' : 'non');

            $facteurDeCriticite = $row['FACTDANGE_DOSSIER'] ?? '';
            $idAvis = is_numeric($row['ID_AVIS'] ?? null) ? (int) $row['ID_AVIS'] : 0;
            if (Avis::DEFAVORABLE !== $idAvis) {
                $facteurDeCriticite = '';
            }

            $this->writeTextCell($sheet, 'X'.$ligne, $facteurDeCriticite);

            $this->writeTextCell($sheet, 'Y'.$ligne, $row['GROUPEMENTS'] ?? '');
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

        $sheet->setCellValue('A1', 'Groupement');
        $sheet->setCellValue('B1', 'Commune');
        $sheet->setCellValue('C1', 'Catégorie');
        $sheet->setCellValue('D1', 'Type');
        $sheet->setCellValue('E1', 'Activité');
        $sheet->setCellValue('F1', 'Code/identifiant établissement');
        $sheet->setCellValue('G1', 'Libellé établissement');
        $sheet->setCellValue('H1', 'Statut');
        $sheet->setCellValue('I1', 'Genre');
        $sheet->setCellValue('J1', 'Type du dossier');
        $sheet->setCellValue('K1', 'Nature du dossier');
        $sheet->setCellValue('L1', 'Date de création du dossier');
        $sheet->setCellValue('M1', 'Objet du dossier');
        $sheet->setCellValue('N1', 'Numéro document urbanisme');
        $sheet->setCellValue('O1', 'Date de visite');
        $sheet->setCellValue('P1', 'Date de la commission en salle');
        $sheet->setCellValue('Q1', 'Commission du dossier');
        $sheet->setCellValue('R1', 'Avis rapporteur');
        $sheet->setCellValue('S1', 'Avis commission');
        $sheet->setCellValue('T1', 'Préventionniste en charge du dossier');
        $sheet->setCellValue('U1', 'Pièces jointes ?');
        $sheet->setCellValue('V1', 'Identifiant PLATAU');

        $ligne = 2;
        foreach ($rows as $row) {
            $this->writeTextCell($sheet, 'A'.$ligne, $row['LIBELLE_GROUPEMENT'] ?? '');
            $this->writeTextCell($sheet, 'B'.$ligne, $row['LIBELLE_COMMUNE'] ?? '');
            $this->writeTextCell($sheet, 'C'.$ligne, $row['LIBELLE_CATEGORIE'] ?? '');
            $this->writeTextCell($sheet, 'D'.$ligne, $row['LIBELLE_TYPE_ETABLISSEMENT'] ?? '');
            $this->writeTextCell($sheet, 'E'.$ligne, $row['LIBELLE_ACTIVITE'] ?? '');
            $this->writeTextCell($sheet, 'F'.$ligne, $row['NUMEROID_ETABLISSEMENT'] ?? '');
            $this->writeTextCell($sheet, 'G'.$ligne, $row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
            $this->writeTextCell($sheet, 'H'.$ligne, $row['LIBELLE_STATUT'] ?? '');
            $this->writeTextCell($sheet, 'I'.$ligne, $row['LIBELLE_GENRE'] ?? '');
            $this->writeTextCell($sheet, 'J'.$ligne, $row['LIBELLE_DOSSIERTYPE'] ?? '');
            $this->writeTextCell($sheet, 'K'.$ligne, $row['LIBELLE_DOSSIERNATURE'] ?? '');

            $this->writeDateCell($sheet, 'L'.$ligne, $row['DATEINSERT_DOSSIER'] ?? null);

            $this->writeTextCell($sheet, 'M'.$ligne, $row['OBJET_DOSSIER'] ?? '');
            $this->writeTextCell($sheet, 'N'.$ligne, $row['NUM_DOCURBA'] ?? '');

            $this->writeDateCell($sheet, 'O'.$ligne, $row['DATEVISITE_DOSSIER'] ?? null);

            $this->writeDateCell($sheet, 'P'.$ligne, $row['DATECOMM_DOSSIER'] ?? null);

            $this->writeTextCell($sheet, 'Q'.$ligne, $row['LIBELLE_COMMISSION'] ?? '');
            $this->writeTextCell($sheet, 'R'.$ligne, $row['LIBELLE_AVIS_RAPPORTEUR'] ?? '');
            $this->writeTextCell($sheet, 'S'.$ligne, $row['LIBELLE_AVIS_COMMISSION'] ?? '');
            $this->writeTextCell($sheet, 'T'.$ligne, trim($this->toStr($row['PRENOM_UTILISATEURINFORMATIONS'] ?? null).' '.$this->toStr($row['NOM_UTILISATEURINFORMATIONS'] ?? null)));

            $sheet->setCellValue('U'.$ligne, '' !== ($row['ID_PIECEJOINTE'] ?? '') ? 'Oui' : 'Non');

            if ('1' === $provenance) {
                $this->writeTextCell($sheet, 'V'.$ligne, $row['ID_PLATAU'] ?? '');
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
     */
    private function writeTextCell(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        $sheet->setCellValueExplicit($coordinate, is_scalar($value) ? (string) $value : '', DataType::TYPE_STRING);
    }

    /**
     * Écrit une date au format dd/mm/yyyy dans une cellule à partir d'une valeur brute YYYY-MM-DD.
     * Contourne le bug du writer ODS de PhpSpreadsheet qui n'applique pas les format codes de date
     * (TYPE_NUMERIC écrit toujours office:value-type="float" sans vérifier le format de date).
     *
     * @param mixed $rawDate Valeur brute YYYY-MM-DD (ou YYYY-MM-DD HH:MM:SS)
     */
    private function writeDateCell(Worksheet $sheet, string $coordinate, mixed $rawDate): void
    {
        if (!is_string($rawDate) || '' === $rawDate) {
            return;
        }

        $date = \DateTime::createFromFormat('Y-m-d', substr($rawDate, 0, 10));
        if ($date instanceof \DateTime) {
            $this->writeTextCell($sheet, $coordinate, $date->format('d/m/Y'));
        }
    }
}
