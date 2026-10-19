<?php

namespace App\Service;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Constant\GenreEtablissement;
use App\Constant\NomInterneCapsule;
use App\Constant\StatutEtablissement;
use App\Constant\TypeActivite;
use App\Constant\TypePrescription;
use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\PieceJointe;
use App\Entity\PrescriptionDossier;
use App\Entity\UtilisateurInformations;
use App\Repository\CommuneRepository;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\GroupementRepository;
use App\Repository\ListeDocumentConsulteRepository;
use App\Service\DataStore\DataStore;
use App\Service\Dossier\DossierVisiteConvocationService;
use App\Service\Odt\OdtWriter;
use App\Service\Rapport\PrescriptionArrayHelper;
use App\Utils\DateFormatterFr;
use App\Utils\PathNormalizer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Odtphp\Exceptions\OdfException;
use Odtphp\Odf;
use Odtphp\Segment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Port direct de creationdocAction() (DossierController) + creationdoc.phtml.
 *
 * Génère un rapport ODT pour un dossier/établissement et retourne un lien HTML
 * vers la pièce jointe nouvellement créée.
 */
class DossierRapportOdtService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly Connection $conn, private readonly DataStore $dataStore, private readonly DossierPrescriptionService $prescriptionService, private readonly DossierAffectationRepository $dossierAffectationRepository, private readonly EtablissementAdresseRepository $etablissementAdresseRepository, private readonly DateCommissionRepository $dateCommissionRepository, private readonly Descriptif $descriptifService, private readonly ListeDocumentConsulteRepository $listeDocRepository, private readonly DossierRepository $dossierRepository, private readonly UrlGeneratorInterface $urlGenerator, private readonly OdtWriter $odtWriter, private readonly DossierVisiteConvocationService $visiteConvocationService, private readonly string $realDataPath, private readonly LoggerInterface $rapportLogger, private readonly CommuneRepository $communeRepository, private readonly GroupementRepository $groupementRepository)
    {
    }

    /**
     * Génère un rapport ODT pour un dossier/établissement.
     *
     * Port direct de creationdocAction() + creationdoc.phtml.
     * Retourne un lien HTML vers la pièce jointe créée.
     *
     * @param int    $idDossier     ID du dossier
     * @param int    $idEtab        ID de l'établissement
     * @param string $fichierSelect Nom du fichier ODT template
     * @param bool   $repriseCC     Si true, désactive la logique cellule CC
     *
     * @return string HTML : <a href='...'>Ouvrir le rapport...</a><br/><br/>
     */
    public function generer(int $idDossier, int $idEtab, string $fichierSelect, bool $repriseCC = false): string
    {
        // --- Chargement des entités ---
        /** @var Dossier|null $dossier */
        $dossier = $this->em->find(Dossier::class, $idDossier);
        /** @var Etablissement|null $etablissement */
        $etablissement = $this->em->find(Etablissement::class, $idEtab);

        if (null === $dossier || null === $etablissement) {
            return '';
        }

        // --- Lignes brutes (DBAL) ---
        /** @var array<string, string|null>|false $rawDossierFetch */
        $rawDossierFetch = $this->conn->fetchAssociative(
            'SELECT * FROM dossier WHERE ID_DOSSIER = :id',
            ['id' => $idDossier]
        );
        /** @var array<string, string|null>|false $rawEtabFetch */
        $rawEtabFetch = $this->conn->fetchAssociative(
            'SELECT * FROM etablissement WHERE ID_ETABLISSEMENT = :id',
            ['id' => $idEtab]
        );
        /** @var array<string, string|null>|false $rawEtabInfosFetch */
        $rawEtabInfosFetch = $this->conn->fetchAssociative(
            'SELECT ei.*, t.LIBELLE_TYPE, ta.LIBELLE_ACTIVITE, g.LIBELLE_GENRE,
                    cat.LIBELLE_CATEGORIE, f.LIBELLE_FAMILLE
             FROM etablissementinformations ei
             LEFT JOIN type t ON ei.ID_TYPE = t.ID_TYPE
             LEFT JOIN typeactivite ta ON ei.ID_TYPEACTIVITE = ta.ID_TYPEACTIVITE
             LEFT JOIN genre g ON ei.ID_GENRE = g.ID_GENRE
             LEFT JOIN categorie cat ON ei.ID_CATEGORIE = cat.ID_CATEGORIE
             LEFT JOIN famille f ON ei.ID_FAMILLE = f.ID_FAMILLE
             WHERE ei.ID_ETABLISSEMENT = :idEtab
             ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC LIMIT 1',
            ['idEtab' => $idEtab]
        );

        if (false === $rawDossierFetch || false === $rawEtabFetch) {
            return '';
        }

        /** @var array<string, string|null> $rawDossier */
        $rawDossier = $rawDossierFetch;
        /** @var array<string, string|null> $rawEtab */
        $rawEtab = $rawEtabFetch;
        /** @var array<string, string|null> $rawEtabInfos */
        $rawEtabInfos = false !== $rawEtabInfosFetch ? $rawEtabInfosFetch : [];

        // --- Type dossier et nature (partagés entre fillSegmentsPrescriptions et listeDocs) ---
        $typeDossierId = $dossier->getType()?->getId();

        $dossierNature = $this->dossierRepository->getDossierNatureId($idDossier);
        $natureDossierLabel = '';
        if (null !== $dossierNature) {
            /** @var array<string, string|null>|false $natureRowFetch */
            // Port direct de Model_DbTable_DossierNature::getDossierNatureLibelle()
            // La table `nature` n'existe pas — la jointure est dossiernature → dossiernatureliste
            $natureRowFetch = $this->conn->fetchAssociative(
                'SELECT LIBELLE_DOSSIERNATURE FROM dossiernatureliste WHERE ID_DOSSIERNATURE = :id',
                ['id' => $dossierNature]
            );
            if (false !== $natureRowFetch) {
                $natureDossierLabel = $natureRowFetch['LIBELLE_DOSSIERNATURE'] ?? '';
            }
        }

        // --- Adresse établissement (numInsee utilisé par fillChampsMaire) ---
        // Résolution par genre d'établissement (site => enfant à l'effectif max, cellule => établissement
        // parent direct, cf. EtablissementAdresseRepository::get()), comme pour les documents de commission.
        /** @var array<int, EtablissementAdresse> $etablissementAdresses */
        $etablissementAdresses = $this->etablissementAdresseRepository->get($idEtab);
        $premiereAdresse = null;
        foreach ($etablissementAdresses as $adresseCandidate) {
            if (null !== $adresseCandidate->getRue()) {
                $premiereAdresse = $adresseCandidate;
                break;
            }
        }

        $commune = $premiereAdresse?->getCommune();
        $rue = $premiereAdresse?->getRue();
        /** @var array<string, string|null> $adresseRow */
        $adresseRow = [
            'NUMERO_ADRESSE' => $premiereAdresse?->getNumero(),
            'LIBELLE_RUE' => $rue?->getLibelle(),
            'LIBELLE_COMMUNE' => $commune?->getLibelle(),
            'CODEPOSTAL_COMMUNE' => $commune?->getCodePostal(),
            'NUMINSEE_COMMUNE' => $commune?->getId(),
        ];
        $numInsee = $adresseRow['NUMINSEE_COMMUNE'] ?? null;

        $this->rapportLogger->debug('Début génération rapport ODT.', ['dossier_id' => $idDossier]);

        try {
            // --- Init Odf ---
            $odf = new Odf($fichierSelect);

            // --- Champs scalaires du dossier (commission, objet, servInstructeur, avis…) ---
            $this->fillChampsDossier($odf, $dossier, $rawDossier, $idDossier, $natureDossierLabel);

            // --- Champs dates du dossier ---
            $this->fillChampsDatesDossier($odf, $rawDossier, $dossier);

            // =====================================================================
            // SEGMENT : preventionnistes
            // =====================================================================
            /** @var array<int, array<string, string|null>> $preventionnisteRows */
            $preventionnisteRows = $this->conn->fetchAllAssociative(
                'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS,
                        ui.GRADE_UTILISATEURINFORMATIONS, ui.TELFIXE_UTILISATEURINFORMATIONS,
                        ui.MAIL_UTILISATEURINFORMATIONS
                 FROM dossierpreventionniste dp
                 JOIN utilisateur u ON dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR
                 JOIN utilisateurinformations ui ON u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                 WHERE dp.ID_DOSSIER = :id',
                ['id' => $idDossier]
            );
            /** @var list<string> $listeMails */
            $listeMails = [];
            try {
                $segPreventionnistes = $odf->setSegment('preventionnistes');
                foreach ($preventionnisteRows as $prev) {
                    $this->addChamp($segPreventionnistes, 'grade', $prev['GRADE_UTILISATEURINFORMATIONS'] ?? '');
                    $this->addChamp($segPreventionnistes, 'nom', $prev['NOM_UTILISATEURINFORMATIONS'] ?? '');
                    $this->addChamp($segPreventionnistes, 'prenom', $prev['PRENOM_UTILISATEURINFORMATIONS'] ?? '');
                    $segPreventionnistes->merge();
                    if (!empty($prev['MAIL_UTILISATEURINFORMATIONS'])) {
                        $listeMails[] = $prev['MAIL_UTILISATEURINFORMATIONS'];
                    }
                }

                $odf->mergeSegment($segPreventionnistes);
            } catch (OdfException) {
            }

            try {
                $segPrevTel = $odf->setSegment('preventionnistesTel');
                foreach ($preventionnisteRows as $prev) {
                    $this->addChamp($segPrevTel, 'tel', $prev['TELFIXE_UTILISATEURINFORMATIONS'] ?? '');
                    $segPrevTel->merge();
                }

                $odf->mergeSegment($segPrevTel);
            } catch (OdfException) {
            }

            $this->addChamp($odf, 'listeMailPreventioniste', implode(', ', $listeMails));

            // =====================================================================
            // SEGMENT : listeDocs (documents de visite)
            // =====================================================================
            // Port direct legacy : sélection de la liste de docs selon type et nature du dossier
            // TYPE_DOSSIER 2/3 = visite : RT(20,25)→getDocVisiteRT, VAO(47,48)→getDocVisiteVAO, sinon→getDocVisite
            // TYPE_DOSSIER 1 = étude : nature 7/19→getDocVisite, sinon→getDocEtude
            // Autre : pas de docs
            $dosssierTypeInt = (int) $typeDossierId;
            $dossierNatureInt = (int) $dossierNature;
            if (in_array($dosssierTypeInt, DossierType::TYPES_VISITE, true)) {
                if (in_array($dossierNatureInt, DossierNature::RECEPTIONS_TRAVAUX, true)) {
                    $listeDocs = $this->listeDocRepository->getDocVisiteRT();
                } elseif (in_array($dossierNatureInt, DossierNature::VISITE_AVANT_OUVERTURE, true)) {
                    $listeDocs = $this->listeDocRepository->getDocVisiteVAO();
                } else {
                    $listeDocs = $this->listeDocRepository->getDocVisite();
                }
            } elseif (DossierType::ETUDE === $dosssierTypeInt) {
                if (in_array($dossierNatureInt, DossierNature::LEVEES, true)) {
                    $listeDocs = $this->listeDocRepository->getDocVisite();
                } else {
                    $listeDocs = $this->listeDocRepository->getDocEtude();
                }
            } else {
                $listeDocs = [];
            }

            try {
                $segListeDocs = $odf->setSegment('listeDocs');
                /** @var array<int, array<string, string|null>> $docsConsultes */
                $docsConsultes = $this->conn->fetchAllAssociative(
                    'SELECT ID_DOC, REF_CONSULTE, DATE_CONSULTE FROM dossierdocconsulte WHERE ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
                /** @var array<int, array<string, string|null>> $docsConsultesIdx */
                $docsConsultesIdx = [];
                foreach ($docsConsultes as $dc) {
                    $docKey = (int) ($dc['ID_DOC'] ?? 0);
                    $docsConsultesIdx[$docKey] = $dc;
                }

                foreach ($listeDocs as $doc) {
                    $docId = (int) ($doc->getId() ?? 0);
                    $consulte = $docsConsultesIdx[$docId] ?? null;
                    // Port direct legacy : seuls les documents consultés sont inclus (avec coche "X")
                    if (null === $consulte) {
                        continue;
                    }

                    $this->addChamp($segListeDocs, 'libelledoc', $doc->getLibelle() ?? '');
                    $this->addChamp($segListeDocs, 'exist', 'X');
                    $this->addChamp($segListeDocs, 'refdoc', $consulte['REF_CONSULTE'] ?? '');
                    $dateDc = '';
                    if (!empty($consulte['DATE_CONSULTE']) && !str_starts_with($consulte['DATE_CONSULTE'], '0000-00-00')) {
                        $dtDc = \DateTime::createFromFormat('Y-m-d', substr($consulte['DATE_CONSULTE'], 0, 10));
                        if ($dtDc instanceof \DateTime) {
                            $dateDc = $dtDc->format('d/m/Y');
                        }
                    }

                    $this->addChamp($segListeDocs, 'datedoc', $dateDc);
                    $segListeDocs->merge();
                }

                $odf->mergeSegment($segListeDocs);
            } catch (OdfException) {
            }

            // =====================================================================
            // SEGMENT : listeDocsAjout
            // =====================================================================
            try {
                $segDocsAjout = $odf->setSegment('listeDocsAjout');
                /** @var array<int, array<string, string|null>> $docsAjout */
                $docsAjout = $this->conn->fetchAllAssociative(
                    'SELECT LIBELLE_DOCAJOUT, REF_DOCAJOUT, DATE_DOCAJOUT FROM listedocajout WHERE ID_DOSSIER = :id ORDER BY ID_DOCAJOUT',
                    ['id' => $idDossier]
                );
                foreach ($docsAjout as $da) {
                    $this->addChamp($segDocsAjout, 'exist', 'X');
                    $this->addChamp($segDocsAjout, 'libelledoc', $da['LIBELLE_DOCAJOUT'] ?? '');
                    $this->addChamp($segDocsAjout, 'refdoc', $da['REF_DOCAJOUT'] ?? '');
                    $dateDa = '';
                    if (!empty($da['DATE_DOCAJOUT'])) {
                        $dtDa = \DateTime::createFromFormat('Y-m-d', substr($da['DATE_DOCAJOUT'], 0, 10));
                        if ($dtDa instanceof \DateTime) {
                            $dateDa = $dtDa->format('d/m/Y');
                        }
                    }

                    $this->addChamp($segDocsAjout, 'datedoc', $dateDa);
                    $segDocsAjout->merge();
                }

                $odf->mergeSegment($segDocsAjout);
            } catch (OdfException) {
            }

            // --- Segments prescriptions (standard + CC) ---
            $this->fillSegmentsPrescriptions($odf, $dossier, (int) $dossierNature, $repriseCC, $rawEtabInfos, $rawDossier, $idEtab);

            // =====================================================================
            // SEGMENT : texteAppl
            // =====================================================================
            try {
                $segTexte = $odf->setSegment('texteAppl');
                /** @var array<int, array<string, string|null>> $textesAppl */
                $textesAppl = $this->conn->fetchAllAssociative(
                    'SELECT ta.LIBELLE_TEXTESAPPL FROM dossiertextesappl dta
                     JOIN textesappl ta ON dta.ID_TEXTESAPPL = ta.ID_TEXTESAPPL
                     WHERE dta.ID_DOSSIER = :id ORDER BY ta.ID_TYPETEXTEAPPL',
                    ['id' => $idDossier]
                );
                foreach ($textesAppl as $ta) {
                    $this->addChamp($segTexte, 'libelleTexteAppl', $ta['LIBELLE_TEXTESAPPL'] ?? '');
                    $segTexte->merge();
                }

                $odf->mergeSegment($segTexte);
            } catch (OdfException) {
            }

            // --- Champs établissement (infos générales, adresse, effectifs, avis) ---
            $categorieEffective = $etablissement->getCategorieEffective();
            $this->fillChampsEtablissement($odf, $dossier, $rawEtab, $rawEtabInfos, $adresseRow, $rawDossier, $idDossier, $idEtab, null !== $categorieEffective ? ($categorieEffective->getLibelle() ?? '') : '');

            // --- Champs DESCTECH ---
            $this->fillChampsDescTech($odf, $rawEtab);

            // --- Maire ---
            $this->fillChampsMaire($odf, $numInsee);

            // --- 11 contacts (DUS, Exploitant, etc.) ---
            $this->fillChampsContacts($odf, $idDossier, $idEtab);

            // --- Établissement père ---
            $this->fillChampsEtablissementPere($odf, $idEtab);

            // =====================================================================
            // GN (Groupement de Nuit)
            // =====================================================================
            // GN dérivé de ID_GENRE (ERP = 2, CELLULE = 3)
            $gn = (int) ($rawEtabInfos['ID_GENRE'] ?? '0');
            if (GenreEtablissement::ERP === $gn) {
                $this->addChamp($odf, 'gn2', '×');
                $this->addChamp($odf, 'gn3', '');
            } elseif (GenreEtablissement::CELLULE === $gn) {
                $this->addChamp($odf, 'gn2', '');
                $this->addChamp($odf, 'gn3', '×');
            }

            // =====================================================================
            // SEGMENTS : avisDerogations + avisDerogationsEtablissement
            // =====================================================================
            $this->applyAvisDerogations($odf, $idDossier, 'avisDerogations', false, $idEtab);
            $this->applyAvisDerogations($odf, $idDossier, 'avisDerogationsEtablissement', true, $idEtab);

            // =====================================================================
            // FORMULAIRES (capsules rubriques)
            // =====================================================================
            $this->applyFormulaires($odf, $dossier, $etablissement);

            // --- Création PièceJointe + sauvegarde ODT ---
            return $this->persistPieceJointe($odf, $dossier, $fichierSelect, $rawDossier, $rawEtabInfos, $rawEtab, $idDossier);
        } catch (\Exception $exception) {
            $this->rapportLogger->error('Erreur lors de la génération du rapport ODT.', [
                'dossier_id' => $idDossier,
                'exception' => $exception,
            ]);
            throw $exception;
        }
    }

    // =========================================================================
    // SOUS-MÉTHODES DE generer() — REF-R-04
    // =========================================================================
    /**
     * Remplit les champs scalaires du dossier : commission, objet, servInstructeur,
     * dérogations, docManquant et les libellés typeDossier/natureDossier.
     *
     * @param array<string, string|null> $rawDossier
     */
    private function fillChampsDossier(
        Odf $odf,
        Dossier $dossier,
        array $rawDossier,
        int $idDossier,
        string $natureDossierLabel
    ): void {
        // --- dateDuJour ---
        $this->addChamp($odf, 'dateDuJour', new \DateTime()->format('d/m/Y'));

        // --- Type et nature dossier ---
        $this->addChamp($odf, 'typeDossier', $dossier->getType()?->getLibelle() ?? '');
        $this->addChamp($odf, 'natureDossier', $natureDossierLabel);

        // --- Commission ---
        $this->addChamp($odf, 'libelle_commission', $dossier->getCommission()?->getLibelle() ?: 'Aucune commission');

        // --- dateCommEntete ---
        $dateCommEntete = 'Indisponible';
        if (!empty($rawDossier['DATECOMM_DOSSIER'])) {
            $dtComm = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DATECOMM_DOSSIER'], 0, 10));
            if ($dtComm instanceof \DateTime) {
                $dateCommEntete = $dtComm->format('d/m/Y');
            }
        }

        $this->addChamp($odf, 'date_commEntete', $dateCommEntete);

        // --- numWinPrev (champ identifiant WinPrev de l'établissement) ---
        // Retiré de fillChampsDossier car nécessite $rawEtab, pas $rawDossier

        // --- objetDossier ---
        $this->addChamp($odf, 'objetDossier', $dossier->getObjet() ?? '');

        // --- analyseRisque ---
        $this->addChamp($odf, 'analyseRisque', $dossier->getAnalyseDeRisque() ?? '');

        // --- observationDossier ---
        $this->addChamp($odf, 'observationDossier', $dossier->getObservations() ?? '');

        // --- descriptifDossier ---
        $this->addChamp($odf, 'descriptifDossier', $dossier->getDescriptif() ?? '');

        // --- criticite ---
        $facteurDangerosite = $dossier->getFacteurDangerosite();
        $this->addChamp($odf, 'criticite', null !== $facteurDangerosite ? (string) $facteurDangerosite : '');

        // --- horsDelai ---
        $horsDelai = ('1' === $rawDossier['HORSDELAI_DOSSIER']) ? 'Oui' : 'Non';
        $this->addChamp($odf, 'horsDelai', $horsDelai);

        // --- docManquant ---
        /** @var array<string, string|null>|false $docManquantRowFetch */
        $docManquantRowFetch = $this->conn->fetchAssociative(
            'SELECT DOCMANQUANT, DATE_DOCSMANQUANT, DATE_RECEPTION_DOC FROM dossierdocmanquant WHERE ID_DOSSIER = :id ORDER BY NUM_DOCSMANQUANT DESC LIMIT 1',
            ['id' => $idDossier]
        );
        /** @var array<string, string|null> $docManquantRow */
        $docManquantRow = false !== $docManquantRowFetch ? $docManquantRowFetch : [];
        $this->addChamp($odf, 'docManquant', $docManquantRow['DOCMANQUANT'] ?? '');
        $dateDocManquant = '';
        if (!empty($docManquantRow['DATE_DOCSMANQUANT'])) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($docManquantRow['DATE_DOCSMANQUANT'], 0, 10));
            if ($dt instanceof \DateTime) {
                $dateDocManquant = $dt->format('d/m/Y');
            }
        }

        $this->addChamp($odf, 'dateDocManquant', $dateDocManquant);
        $dateReceptionDocManquant = '';
        if (!empty($docManquantRow['DATE_RECEPTION_DOC'])) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($docManquantRow['DATE_RECEPTION_DOC'], 0, 10));
            if ($dt instanceof \DateTime) {
                $dateReceptionDocManquant = $dt->format('d/m/Y');
            }
        }

        $this->addChamp($odf, 'dateReceptionDocManquant', $dateReceptionDocManquant);

        // --- demandeurDossier ---
        $this->addChamp($odf, 'demandeurDossier', $dossier->getDemandeur() ?? '');

        // --- servInstructeur ---
        $contact = $this->getContactServiceInstructeur($dossier);
        $this->addChamp($odf, 'servInstructeur', $dossier->getServiceInstructeur() ?? '');
        $this->addChamp($odf, 'servInstructeurPrenomContact', $contact?->getPrenom() ?? '');
        $this->addChamp($odf, 'servInstructeurNomContact', $contact?->getNom() ?? '');
        $this->addChamp($odf, 'servInstructeurMail', $contact?->getMail() ?? '');

        // --- Dérogations dossier ---
        $this->addChamp($odf, 'reglesDerog', $dossier->getReglesDerogation() ?? '');
        $this->addChamp($odf, 'justificationDerog', $dossier->getJustificationDerogation() ?? '');
        $this->addChamp($odf, 'mesuresCompensatoiresDerog', $dossier->getMesuresCompensatoires() ?? '');
        $this->addChamp($odf, 'mesuresComplementairesDerog', $dossier->getMesuresComplementaires() ?? '');
    }

    private function getContactServiceInstructeur(Dossier $dossier): ?UtilisateurInformations
    {
        $serviceInstructeur = $dossier->getServiceInstructeur();
        if (null === $serviceInstructeur) {
            return null;
        }

        return match ($dossier->getTypeServiceInstructeur()) {
            'servInstCommune' => $this->communeRepository->findFirstByLibelle($serviceInstructeur)?->getUtilisateurInformations(),
            'servInstGrp' => $this->groupementRepository->findOneBy(['libelle' => $serviceInstructeur])?->getInformations(),
            default => null,
        };
    }

    /**
     * Remplit les champs de dates du dossier (dateVisite, dateComm, dateRvrat, etc.).
     *
     * @param array<string, string|null> $rawDossier
     */
    private function fillChampsDatesDossier(Odf $odf, array $rawDossier, Dossier $dossier): void
    {
        // --- dateRvrat ---
        $dateRvrat = 'Indisponible';
        if (!empty($rawDossier['DATERVRAT_DOSSIER'])) {
            $dtRvrat = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DATERVRAT_DOSSIER'], 0, 10));
            if ($dtRvrat instanceof \DateTime) {
                $dateRvrat = DateFormatterFr::formatLong($dtRvrat);
            }
        }

        $this->addChamp($odf, 'dateRvrat', $dateRvrat);

        // --- dateDelaipresc ---
        $dateDelaipresc = 'Pas de date';
        if (!empty($rawDossier['DELAIPRESC_DOSSIER'])) {
            $dtDelai = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DELAIPRESC_DOSSIER'], 0, 10));
            if ($dtDelai instanceof \DateTime) {
                $dateDelaipresc = DateFormatterFr::formatLong($dtDelai);
            }
        }

        $this->addChamp($odf, 'dateDelaipresc', $dateDelaipresc);

        // --- dateVisite / heureVisite ---
        // Port du legacy DossierController::creationdocAction() : la colonne DATEVISITE_DOSSIER
        // ne stocke qu'une date (pas d'heure), l'heure réelle de convocation vient du groupe de
        // dates de commission liées à l'affectation visite/groupe de visite du dossier.
        $infosConvocation = $this->visiteConvocationService->getInfosConvocation($dossier);

        $this->addChamp($odf, 'dateVisite', $infosConvocation['dateVisite']);
        $this->addChamp($odf, 'heureVisite', $infosConvocation['heureVisite']);

        // --- dateGeneration ---
        $this->addChamp($odf, 'dateGeneration', DateFormatterFr::formatLong(new \DateTime()));

        // --- dateComm ---
        $this->addChamp($odf, 'dateComm', DateFormatterFr::formatLongFromStr($rawDossier['DATECOMM_DOSSIER'] ?? null));

        // --- dateIncomplet ---
        $this->addChamp($odf, 'dateIncomplet', DateFormatterFr::formatLongFromStr($rawDossier['DATEINCOMPLET_DOSSIER'] ?? null));

        // =====================================================================
        // DATES (dépôts)
        // =====================================================================
        $this->addChamp($odf, 'dateDepotMairie', DateFormatterFr::formatShort($rawDossier['DATEMAIRIE_DOSSIER'] ?? null));
        $this->addChamp($odf, 'dateDepotSec', DateFormatterFr::formatShort($rawDossier['DATESECRETARIAT_DOSSIER'] ?? null));
        $this->addChamp($odf, 'dateSDIS', DateFormatterFr::formatShort($rawDossier['DATESDIS_DOSSIER'] ?? null));
        $this->addChamp($odf, 'dateCreationDossier', DateFormatterFr::formatShort($rawDossier['DATEINSERT_DOSSIER'] ?? null));
    }

    /**
     * Remplit les champs de l'établissement : infos générales, adresse, effectifs,
     * avis dossier, prochaine/dernière visite périodique.
     *
     * @param array<string, string|null> $rawEtab
     * @param array<string, string|null> $rawEtabInfos
     * @param array<string, string|null> $adresseRow
     * @param array<string, string|null> $rawDossier
     */
    private function fillChampsEtablissement(
        Odf $odf,
        Dossier $dossier,
        array $rawEtab,
        array $rawEtabInfos,
        array $adresseRow,
        array $rawDossier,
        int $idDossier,
        int $idEtab,
        string $libelleCategorie
    ): void {
        // =====================================================================
        // ÉTABLISSEMENT — informations générales
        // =====================================================================
        $this->addChamp($odf, 'genreEtab', $rawEtabInfos['LIBELLE_GENRE'] ?? '');
        $this->addChamp($odf, 'etablissementLibelle', $rawEtabInfos['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'typeLettreP', $rawEtabInfos['LIBELLE_TYPE'] ?? '');
        $this->addChamp($odf, 'activiteP', $rawEtabInfos['LIBELLE_ACTIVITE'] ?? '');
        $this->addChamp($odf, 'numWinPrev', $rawEtab['NUMEROID_ETABLISSEMENT'] ?? '');

        // Types/activités secondaires
        $typeLettreS = '';
        $activiteS = '';
        $idEtabInfos = $rawEtabInfos['ID_ETABLISSEMENTINFORMATIONS'] ?? null;
        if (null !== $idEtabInfos) {
            /** @var array<int, array<string, string|null>> $secondaires */
            $secondaires = $this->conn->fetchAllAssociative(
                'SELECT etas.*, t.LIBELLE_TYPE, ta.LIBELLE_ACTIVITE
                 FROM etablissementinformationstypesactivitessecondaires etas
                 LEFT JOIN type t ON etas.ID_TYPE_SECONDAIRE = t.ID_TYPE
                 LEFT JOIN typeactivite ta ON etas.ID_TYPEACTIVITE_SECONDAIRE = ta.ID_TYPEACTIVITE
                 WHERE etas.ID_ETABLISSEMENTINFORMATIONS = :id',
                ['id' => $idEtabInfos]
            );
            $typesS = [];
            $activitesS = [];
            foreach ($secondaires as $sec) {
                $typesS[] = $sec['LIBELLE_TYPE'] ?? '';
                $activitesS[] = $sec['LIBELLE_ACTIVITE'] ?? '';
            }

            $typeLettreS = implode(', ', $typesS);
            $activiteS = implode(', ', $activitesS);
        }

        $this->addChamp($odf, 'typeLettreS', $typeLettreS);
        $this->addChamp($odf, 'activiteS', $activiteS);

        // Commune établissement
        $communeEtab = $adresseRow['LIBELLE_COMMUNE'] ?? '';
        // Port direct legacy : adresse = numéro + rue + code postal + commune en majuscules
        $adresseStr = '';
        if (!empty($adresseRow['NUMERO_ADRESSE'])) {
            $adresseStr .= $adresseRow['NUMERO_ADRESSE'].' ';
        }

        if (!empty($adresseRow['LIBELLE_RUE'])) {
            $adresseStr .= $adresseRow['LIBELLE_RUE'].' ';
        }

        if (!empty($adresseRow['CODEPOSTAL_COMMUNE'])) {
            $adresseStr .= $adresseRow['CODEPOSTAL_COMMUNE'].' ';
        }

        if (!empty($communeEtab)) {
            $adresseStr .= strtoupper($communeEtab).' ';
        }

        $etablissementAdresse = trim($adresseStr);
        $this->addChamp($odf, 'communeEtab', $communeEtab);
        $this->addChamp($odf, 'etablissementAdresse', $etablissementAdresse);

        // Numéro document d'urbanisme
        /** @var array<string, string|null>|false $numDocUrbaRowFetch */
        $numDocUrbaRowFetch = $this->conn->fetchAssociative(
            'SELECT NUM_DOCURBA FROM dossierdocurba WHERE ID_DOSSIER = :id',
            ['id' => $idDossier]
        );
        $this->addChamp($odf, 'numDocUrba', false !== $numDocUrbaRowFetch ? ($numDocUrbaRowFetch['NUM_DOCURBA'] ?? '') : '');

        // Catégorie / Famille — port direct legacy : seul le premier mot de LIBELLE_CATEGORIE est utilisé
        $libelleCat = $libelleCategorie;
        $categorieParts = '' !== $libelleCat ? explode(' ', $libelleCat) : [];
        $this->addChamp($odf, 'categorieEtab', !empty($categorieParts) ? $categorieParts[0] : '');
        $this->addChamp($odf, 'familleEtab', $rawEtabInfos['LIBELLE_FAMILLE'] ?? '');

        // Effectifs
        $numPublic = (int) ($rawEtabInfos['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? '0');
        $numPersonnel = (int) ($rawEtabInfos['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? '0');
        $numHeberge = (int) ($rawEtabInfos['EFFECTIFHEBERGE_ETABLISSEMENTINFORMATIONS'] ?? '0');
        $this->addChamp($odf, 'numPublic', (string) $numPublic);
        $this->addChamp($odf, 'numPersonnel', (string) $numPersonnel);
        $this->addChamp($odf, 'numTotal', (string) ($numPublic + $numPersonnel));
        $this->addChamp($odf, 'numHeberge', (string) $numHeberge);

        // Avis dossier
        $this->addChamp($odf, 'avisDossier', $dossier->getAvis()?->getLibelle() ?? '');

        $avisDossierCommRow = null;
        if (!empty($rawDossier['AVIS_DOSSIER_COMMISSION'])) {
            /** @var array<string, string|null>|false $avisDossierCommRowFetch */
            $avisDossierCommRowFetch = $this->conn->fetchAssociative(
                'SELECT LIBELLE_AVIS FROM avis WHERE ID_AVIS = :id',
                ['id' => $rawDossier['AVIS_DOSSIER_COMMISSION']]
            );
            $avisDossierCommRow = false !== $avisDossierCommRowFetch ? $avisDossierCommRowFetch : null;
        }

        $this->addChamp($odf, 'avisDossierCommission', null !== $avisDossierCommRow ? ($avisDossierCommRow['LIBELLE_AVIS'] ?? '') : '');

        // Etat dossier
        $etatDossier = ('1' === $rawDossier['INCOMPLET_DOSSIER']) ? 'Incomplet' : 'Complet';
        $this->addChamp($odf, 'etatDossier', $etatDossier);
        $this->addChamp($odf, 'idDossier', (string) $idDossier);

        // Téléphones établissement — port direct legacy : TELEPHONE_ETABLISSEMENT, FAX_ETABLISSEMENT, COURRIEL_ETABLISSEMENT
        $this->addChamp($odf, 'numTelEtab', $rawEtab['TELEPHONE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'numFaxEtab', $rawEtab['FAX_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'mailEtab', $rawEtab['COURRIEL_ETABLISSEMENT'] ?? '');

        // Périodicité
        $this->addChamp($odf, 'periodiciteEtab', $rawEtabInfos['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? '');

        // --- Prochaine visite périodique (port direct legacy Service_Etablissement::next_visite()) ---
        // Filtre : VP (TYPE_DOSSIER=2) ou GV (TYPE_DOSSIER=3), avec avis commission rendu,
        // natures périodiques 21, 26 (+ 47, 48 toujours incluses comme en legacy).
        // Si PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE=1 : utilise GREATEST(DATECOMM, DATEVISITE).
        $prochaineVisite = '';
        $periodicite = (int) ($rawEtabInfos['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? 0);
        if ($periodicite > 0) {
            $useDateCommission = filter_var(
                getenv('PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE'),
                FILTER_VALIDATE_BOOLEAN
            );
            if ($useDateCommission) {
                /** @var array<string, string|null>|false $lastVpRowFetch */
                $lastVpRowFetch = $this->conn->fetchAssociative(
                    'SELECT CASE
                          WHEN d.DATECOMM_DOSSIER >= d.DATEVISITE_DOSSIER THEN d.DATECOMM_DOSSIER
                          ELSE d.DATEVISITE_DOSSIER
                        END AS DATE_RELANCE_PERIODICITE
                     FROM dossier d
                     JOIN etablissementdossier ed ON ed.ID_DOSSIER = d.ID_DOSSIER
                     JOIN dossiernature dn ON dn.ID_DOSSIER = d.ID_DOSSIER
                     WHERE ed.ID_ETABLISSEMENT = :idEtab
                       AND d.TYPE_DOSSIER IN (2, 3)
                       AND d.AVIS_DOSSIER_COMMISSION > 0
                       AND dn.ID_NATURE IN (21, 26, 47, 48)
                     ORDER BY DATE_RELANCE_PERIODICITE DESC LIMIT 1',
                    ['idEtab' => $idEtab]
                );
                $dateRef = (false !== $lastVpRowFetch && !empty($lastVpRowFetch['DATE_RELANCE_PERIODICITE']))
                    ? $lastVpRowFetch['DATE_RELANCE_PERIODICITE']
                    : null;
            } else {
                /** @var array<string, string|null>|false $lastVpRowFetch */
                $lastVpRowFetch = $this->conn->fetchAssociative(
                    'SELECT d.DATEVISITE_DOSSIER
                     FROM dossier d
                     JOIN etablissementdossier ed ON ed.ID_DOSSIER = d.ID_DOSSIER
                     JOIN dossiernature dn ON dn.ID_DOSSIER = d.ID_DOSSIER
                     WHERE ed.ID_ETABLISSEMENT = :idEtab
                       AND d.TYPE_DOSSIER IN (2, 3)
                       AND d.AVIS_DOSSIER_COMMISSION > 0
                       AND dn.ID_NATURE IN (21, 26, 47, 48)
                       AND d.DATEVISITE_DOSSIER IS NOT NULL
                     ORDER BY d.DATEVISITE_DOSSIER DESC LIMIT 1',
                    ['idEtab' => $idEtab]
                );
                $dateRef = (false !== $lastVpRowFetch && !empty($lastVpRowFetch['DATEVISITE_DOSSIER']))
                    ? $lastVpRowFetch['DATEVISITE_DOSSIER']
                    : null;
            }

            if (null !== $dateRef) {
                $dateBase = \DateTime::createFromFormat('Y-m-d', substr($dateRef, 0, 10));
                if ($dateBase instanceof \DateTime) {
                    $dateBase->modify('+'.$periodicite.' months');
                    $moisFr = [
                        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
                    ];
                    $prochaineVisite = $moisFr[(int) $dateBase->format('n')].' '.$dateBase->format('Y');
                }
            }
        }

        $this->addChamp($odf, 'prochaineVisite', $prochaineVisite);

        // --- Dernière visite périodique ---
        $dateLastVP = '';
        $avisLastVP = '';
        if (!empty($rawDossier['DATEVISITE_DOSSIER'])) {
            /** @var array<string, string|null>|false $lastVpRowFetch */
            $lastVpRowFetch = $this->conn->fetchAssociative(
                'SELECT d.ID_DOSSIER, d.DATEVISITE_DOSSIER
                 FROM dossier d
                 JOIN etablissementdossier ed ON ed.ID_DOSSIER = d.ID_DOSSIER
                 JOIN dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
                 WHERE ed.ID_ETABLISSEMENT = :idEtab
                 AND d.ID_DOSSIER <> :idDossier
                 AND (dn.ID_NATURE = 21 OR dn.ID_NATURE = 26)
                 AND d.DATEVISITE_DOSSIER IS NOT NULL
                 AND d.DATEVISITE_DOSSIER < :dateVisite
                 ORDER BY d.DATEVISITE_DOSSIER DESC LIMIT 1',
                [
                    'idEtab' => $idEtab,
                    'idDossier' => $idDossier,
                    'dateVisite' => $rawDossier['DATEVISITE_DOSSIER'],
                ]
            );
            if (false !== $lastVpRowFetch) {
                $dateLastVP = DateFormatterFr::formatShort($lastVpRowFetch['DATEVISITE_DOSSIER'] ?? null);
                if (!empty($lastVpRowFetch['ID_DOSSIER'])) {
                    /** @var array<string, string|null>|false $avisLastVpRowFetch */
                    $avisLastVpRowFetch = $this->conn->fetchAssociative(
                        'SELECT a.LIBELLE_AVIS FROM avis a
                         JOIN dossier d ON d.AVIS_DOSSIER_COMMISSION = a.ID_AVIS
                         WHERE d.ID_DOSSIER = :id',
                        ['id' => $lastVpRowFetch['ID_DOSSIER']]
                    );
                    $avisLastVP = false !== $avisLastVpRowFetch ? ($avisLastVpRowFetch['LIBELLE_AVIS'] ?? '') : '';
                }
            }
        }

        $this->addChamp($odf, 'dateLastVP', $dateLastVP);
        $this->addChamp($odf, 'avisLastVP', $avisLastVP);
    }

    /**
     * Remplit les champs techniques de l'établissement (DESCTECH).
     *
     * @param array<string, string|null> $rawEtab
     */
    private function fillChampsDescTech(Odf $odf, array $rawEtab): void
    {
        // =====================================================================
        // Champs DESCTECH — colonnes directes de la table etablissement
        // (le legacy les lit via $this->etabDesc = $model_etablissement->find($idEtab)->current())
        // =====================================================================
        $this->addChamp($odf, 'historiqueDescriptifEtab', $rawEtab['DESCRIPTIF_HISTORIQUE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'descriptifEtab', $rawEtab['DESCRIPTIF_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'derogAccEtab', $rawEtab['DESCRIPTIF_DEROGATIONS_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'surfaceEmpriseSol', $rawEtab['DESCTECH_IMPLANTATION_SURFACE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'SHON', $rawEtab['DESCTECH_IMPLANTATION_SHON_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'SHOB', $rawEtab['DESCTECH_IMPLANTATION_SHOB_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbNiveaux', $rawEtab['DESCTECH_IMPLANTATION_NBNIVEAUX_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'PBDN', $rawEtab['DESCTECH_IMPLANTATION_PBDN_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'surfaceTotale', $rawEtab['DESCTECH_IMPLANTATION_SURFACETOTALE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'surfaceAccessible', $rawEtab['DESCTECH_IMPLANTATION_SURFACEACCPUBLIC_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbFacadeAccess', $rawEtab['DESCTECH_DESSERTE_NBFACADELIBRE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'voieEngin', $rawEtab['DESCTECH_DESSERTE_VOIEENGIN_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'voieEchelle', $rawEtab['DESCTECH_DESSERTE_VOIEECHELLE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'espaceLibre', $rawEtab['DESCTECH_DESSERTE_ESPACELIBRE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'lateralCF', $rawEtab['DESCTECH_ISOLEMENT_LATERALCF_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'superposeCF', $rawEtab['DESCTECH_ISOLEMENT_SUPERPOSECF_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'visAvis', $rawEtab['DESCTECH_ISOLEMENT_VISAVIS_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'structureSF', $rawEtab['DESCTECH_STABILITE_STRUCTURESF_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'plancherSF', $rawEtab['DESCTECH_STABILITE_PLANCHERSF_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'cloisonnementTrad', ('1' === $rawEtab['DESCTECH_DISTRIBUTION_CLOISONNEMENTTRAD_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'secteurs', ('1' === $rawEtab['DESCTECH_DISTRIBUTION_SECTEURS_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'compartiments', ('1' === $rawEtab['DESCTECH_DISTRIBUTION_COMPARTIMENTS_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'locauxRisquesMoyens', $rawEtab['DESCTECH_LOCAUXARISQUE_NBRISQUESMOYENS_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'locauxRisquesImportants', $rawEtab['DESCTECH_LOCAUXARISQUE_NBRISQUESIMPORTANTS_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'espacesAttentesNombre', $rawEtab['DESCTECH_ESPACES_NOMBRE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'espacesAttentesNiveaux', $rawEtab['DESCTECH_ESPACES_NIVEAUCONCERNE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'desenfNaturel', ('1' === $rawEtab['DESCTECH_DESENFUMAGE_NATUREL_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'desenfMecanique', ('1' === $rawEtab['DESCTECH_DESENFUMAGE_MECANIQUE_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'desenfCommentaire', $rawEtab['DESCTECH_DESENFUMAGE_COMMENTAIRE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbChaufferie', $rawEtab['DESCTECH_CHAUFFERIES_NB_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'puissanceMaxChauff', $rawEtab['DESCTECH_CHAUFFERIES_PUISSMAX_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbChauff30kw', $rawEtab['DESCTECH_CHAUFFERIES_NB30KW'] ?? '');
        $this->addChamp($odf, 'nbChauff70kw', $rawEtab['DESCTECH_CHAUFFERIES_NB70KW'] ?? '');
        $this->addChamp($odf, 'nbChauff2mw', $rawEtab['DESCTECH_CHAUFFERIES_NB2MW'] ?? '');
        $this->addChamp($odf, 'puissTotalChaff', $rawEtab['DESCTECH_CHAUFFERIES_PUISSANCETOTALE'] ?? '');
        $this->addChamp($odf, 'locGaz', $rawEtab['DESCTECH_COUPURENRJ_GAZ_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'locElectricite', $rawEtab['DESCTECH_COUPURENRJ_ELEC_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'locPhotovoltaique', $rawEtab['DESCTECH_COUPURENRJ_PHOTOVOLTAIQUE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'locAutre', $rawEtab['DESCTECH_COUPURENRJ_AUTRE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbAscTot', $rawEtab['DESCTECH_ASCENSEURS_NBTOTAL_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbAscAS4', $rawEtab['DESCTECH_ASCENSEURS_NBAS4_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'colSeches', ('1' === $rawEtab['DESCTECH_MOYENSSECOURS_COLONNESSECHES_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'colHumides', ('1' === $rawEtab['DESCTECH_MOYENSSECOURS_COLONNESHUMIDES_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'RIA', ('1' === $rawEtab['DESCTECH_MOYENSSECOURS_RIA_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'sprinkleur', ('1' === $rawEtab['DESCTECH_MOYENSSECOURS_SPRINKLEUR_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'brouillardEau', ('1' === $rawEtab['DESCTECH_MOYENSSECOURS_BROUILLARDEAU_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'presencePCS', ('1' === $rawEtab['DESCTECH_PCSECU_PRESENCE_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'localisationPCS', $rawEtab['DESCTECH_PCSECU_LOCALISATION_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'presenceSSI', ('1' === $rawEtab['DESCTECH_SSI_PRESENCE_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'catSSI', $rawEtab['DESCTECH_SSI_CATEGORIE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'alarmeSSI', $rawEtab['DESCTECH_SSI_ALARME_TYPE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'persQualEL18', ('1' === $rawEtab['DESCTECH_SERVICESECU_EL18_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'persDesignes', ('1' === $rawEtab['DESCTECH_SERVICESECU_PERSONNELSDESIGNES_ETABLISSEMENT']) ? 'Oui' : 'Non');
        $this->addChamp($odf, 'nbAgentSecu', $rawEtab['DESCTECH_SERVICESECU_AGENTDESECU_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbChefEquipeSecu', $rawEtab['DESCTECH_SERVICESECU_CHEFEQUIPE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbChefServSecu', $rawEtab['DESCTECH_SERVICESECU_CHEFDESERVICESECU_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbSp', $rawEtab['DESCTECH_SERVICESECU_SP_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'commentaireSp', $rawEtab['DESCTECH_SERVICESECU_COMMENTAIRESP_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'nbPointEau', $rawEtab['DESCTECH_DEFENSE_PTEAU_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'volumePointEau', $rawEtab['DESCTECH_DEFENSE_VOLUMEPTEAU_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'commentairePointEauNat', $rawEtab['DESCTECH_DEFENSE_PTEAUCOMMENTAIRE_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'PI', $rawEtab['DESCTECH_DEFENSE_PI_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'BI', $rawEtab['DESCTECH_DEFENSE_BI_ETABLISSEMENT'] ?? '');
        $this->addChamp($odf, 'debitSimultane', $rawEtab['DESCTECH_DEFENSE_DEBITSIMULTANE_ETABLISSEMENT'] ?? '');
    }

    /**
     * Remplit les champs relatifs au maire de la commune de l'établissement.
     */
    private function fillChampsMaire(Odf $odf, ?string $numInsee): void
    {
        $nomMaire = '';
        $prenomMaire = '';
        $mailMaire = '';
        $telFixeMaire = '';
        $telPortMaire = '';
        $numFaxMaire = '';
        $numeroMaire = '';
        $rueMaire = '';
        $cpMaire = '';
        $villeMaire = '';
        if (null !== $numInsee) {
            /** @var array<string, string|null>|false $maireRowFetch */
            $maireRowFetch = $this->conn->fetchAssociative(
                'SELECT ui.* FROM adressecommune ac
                 JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = ac.ID_UTILISATEURINFORMATIONS
                 WHERE ac.NUMINSEE_COMMUNE = :numInsee LIMIT 1',
                ['numInsee' => $numInsee]
            );
            if (false !== $maireRowFetch) {
                $nomMaire = $maireRowFetch['NOM_UTILISATEURINFORMATIONS'] ?? '';
                $prenomMaire = $maireRowFetch['PRENOM_UTILISATEURINFORMATIONS'] ?? '';
                $mailMaire = $maireRowFetch['MAIL_UTILISATEURINFORMATIONS'] ?? '';
                $telFixeMaire = $maireRowFetch['TELFIXE_UTILISATEURINFORMATIONS'] ?? '';
                $telPortMaire = $maireRowFetch['TELPORTABLE_UTILISATEURINFORMATIONS'] ?? '';
                $numFaxMaire = $maireRowFetch['TELFAX_UTILISATEURINFORMATIONS'] ?? '';
                // Port direct legacy : colonnes NUMEROADRESSE/RUEADRESSE/CPADRESSE/VILLEADRESSE
                $numeroMaire = $maireRowFetch['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '';
                $rueMaire = $maireRowFetch['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '';
                $cpMaire = $maireRowFetch['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '';
                $villeMaire = $maireRowFetch['VILLEADRESSE_UTILISATEURINFORMATIONS'] ?? '';
            }
        }

        $this->addChamp($odf, 'nomMaire', $nomMaire);
        $this->addChamp($odf, 'prenomMaire', $prenomMaire);
        $this->addChamp($odf, 'mailMaire', $mailMaire);
        $this->addChamp($odf, 'telFixeMaire', $telFixeMaire);
        $this->addChamp($odf, 'telPortMaire', $telPortMaire);
        $this->addChamp($odf, 'numFaxMaire', $numFaxMaire);
        $this->addChamp($odf, 'numeroMaire', $numeroMaire);
        $this->addChamp($odf, 'rueMaire', $rueMaire);
        $this->addChamp($odf, 'cpMaire', $cpMaire);
        $this->addChamp($odf, 'villeMaire', $villeMaire);
    }

    /**
     * Remplit les 11 champs contacts du dossier (DUS, Exploitant, RespSecu, etc.).
     */
    private function fillChampsContacts(Odf $odf, int $idDossier, int $idEtab): void
    {
        // =====================================================================
        // CONTACTS (11 contacts)
        // =====================================================================
        /** @var array<int, array<string, string|int>> $contactMap */
        $contactMap = [
            ['idFonction' => 8,  'prefix' => 'Dus'],
            ['idFonction' => 7,  'prefix' => 'Exploitant'],
            ['idFonction' => 9,  'prefix' => 'RespSecu'],
            ['idFonction' => 17, 'prefix' => 'Proprio'],
            ['idFonction' => 4,  'prefix' => 'MaitreOeuvre'],
            ['idFonction' => 1,  'prefix' => 'Prefet'],
            ['idFonction' => 3,  'prefix' => 'MaitreOuvrage'],
            ['idFonction' => 5,  'prefix' => 'PetitionnaireDemandeur'],
            ['idFonction' => 6,  'prefix' => 'ControllerTechnique'],
            ['idFonction' => 10, 'prefix' => 'Participant'],
            ['idFonction' => 11, 'prefix' => 'Demandeur'],
        ];
        foreach ($contactMap as $contactDef) {
            $idFonction = (int) $contactDef['idFonction'];
            $prefix = (string) $contactDef['prefix'];
            $contact = $this->getContactInfo($idDossier, $idEtab, $idFonction);
            $this->addChamp($odf, 'nom'.$prefix, $contact['nom']);
            // Typo legacy préservée pour le demandeur
            if ('Demandeur' === $prefix) {
                $this->addChamp($odf, 'prenomDemandeurt', $contact['prenom']);
            } else {
                $this->addChamp($odf, 'prenom'.$prefix, $contact['prenom']);
            }

            $this->addChamp($odf, 'mail'.$prefix, $contact['mail']);
            $this->addChamp($odf, 'telFixe'.$prefix, $contact['telFixe']);
            $this->addChamp($odf, 'telPort'.$prefix, $contact['telPort']);
            $this->addChamp($odf, 'numFax'.$prefix, $contact['numFax']);
            $this->addChamp($odf, 'societe'.$prefix, $contact['societe']);
        }
    }

    /**
     * Remplit les champs de l'établissement père (groupement).
     */
    private function fillChampsEtablissementPere(Odf $odf, int $idEtab): void
    {
        // =====================================================================
        // Établissement père
        // =====================================================================
        /** @var array<string, string|null>|false $pereRowFetch */
        $pereRowFetch = $this->conn->fetchAssociative(
            'SELECT el.ID_ETABLISSEMENT AS ID_PERE FROM etablissementlie el WHERE el.ID_FILS_ETABLISSEMENT = :id LIMIT 1',
            ['id' => $idEtab]
        );
        $idPere = false !== $pereRowFetch ? ($pereRowFetch['ID_PERE'] ?? null) : null;

        $etablissementLibelleP = '';
        $typeLettrePPere = '';
        $activitePPere = '';
        $categorieEtabPere = '';
        $genrePere = '';
        if (null !== $idPere) {
            /** @var array<string, string|null>|false $pereInfosRowFetch */
            $pereInfosRowFetch = $this->conn->fetchAssociative(
                'SELECT ei.*, t.LIBELLE_TYPE, ta.LIBELLE_ACTIVITE, g.LIBELLE_GENRE, cat.LIBELLE_CATEGORIE
                 FROM etablissementinformations ei
                 LEFT JOIN type t ON ei.ID_TYPE = t.ID_TYPE
                 LEFT JOIN typeactivite ta ON ei.ID_TYPEACTIVITE = ta.ID_TYPEACTIVITE
                 LEFT JOIN genre g ON ei.ID_GENRE = g.ID_GENRE
                 LEFT JOIN categorie cat ON ei.ID_CATEGORIE = cat.ID_CATEGORIE
                 WHERE ei.ID_ETABLISSEMENT = :id
                 ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC LIMIT 1',
                ['id' => $idPere]
            );
            if (false !== $pereInfosRowFetch) {
                $etablissementLibelleP = $pereInfosRowFetch['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '';
                $typeLettrePPere = $pereInfosRowFetch['LIBELLE_TYPE'] ?? '';
                $activitePPere = $pereInfosRowFetch['LIBELLE_ACTIVITE'] ?? '';
                $categorieEtabPere = $pereInfosRowFetch['LIBELLE_CATEGORIE'] ?? '';
                $genrePere = $pereInfosRowFetch['LIBELLE_GENRE'] ?? '';
            }
        }

        $this->addChamp($odf, 'etablissementLibelleP', $etablissementLibelleP);
        $this->addChamp($odf, 'typeLettrePPere', $typeLettrePPere);
        $this->addChamp($odf, 'activitePPere', $activitePPere);
        $this->addChamp($odf, 'categorieEtabPere', $categorieEtabPere);
        $this->addChamp($odf, 'genrePere', $genrePere);
    }

    /**
     * Remplit les segments de prescriptions (Réglementaire, Exploitation, Amélioration)
     * et les cellules CC si applicable.
     *
     * @param array<string, string|null> $rawEtabInfos
     * @param array<string, string|null> $rawDossier
     */
    private function fillSegmentsPrescriptions(
        Odf $odf,
        Dossier $dossier,
        int $dossierNature,
        bool $repriseCC,
        array $rawEtabInfos,
        array $rawDossier,
        int $idEtab
    ): void {
        // =====================================================================
        // PRESCRIPTIONS — Types Réglementaire, Exploitation, Amélioration
        // =====================================================================
        $prescTypesMap = [
            TypePrescription::REGLEMENTAIRE => ['listeReg', 'listeRegReprises', 'listeRegActuelles'],
            TypePrescription::EXPLOITATION => ['listeExploitation', 'listeExploitationReprises', 'listeExploitationActuelles'],
            TypePrescription::AMELIORATION => ['listeAmelioration', 'listeAmeliorationReprises', 'listeAmeliorationActuelles'],
        ];
        foreach (array_keys($prescTypesMap) as $typeId) {
            $prescriptions = $this->prescriptionService->getPrescriptionsParType($dossier, $typeId);
            $prescArray = $this->buildPrescriptionNestedArray($prescriptions);
            $prescArray = PrescriptionArrayHelper::withoutLevees($prescArray);
            $reprises = PrescriptionArrayHelper::withoutActuals($prescArray);
            $actuelles = PrescriptionArrayHelper::withoutPrevious($prescArray);
            $this->addPrescription($odf, $prescArray, $typeId, 'all');
            $this->addPrescription($odf, $reprises, $typeId, 'reprises');
            $this->addPrescription($odf, $actuelles, $typeId, 'actuelles');
        }

        // =====================================================================
        // CELLULES CC
        // Port direct legacy : if ($this->id_typeactivite == 29) — le bloc cellulesPresc
        // est TOUJOURS traité (setSegment + mergeSegment) pour les CC, quelle que soit
        // la nature du dossier, afin d'éviter les markers [!-- BEGIN/END --] verbatim.
        // =====================================================================
        $idTypeActivite = (int) ($rawEtabInfos['ID_TYPEACTIVITE'] ?? 0);
        if (TypeActivite::CENTRE_COMMERCIAL === $idTypeActivite && !$repriseCC) {
            if (in_array($dossierNature, DossierNature::NATURES_CC, true)) {
                $this->applyCelluleDossier($odf, $dossier);
            } elseif (in_array($dossierNature, DossierNature::LEVEES, true)) {
                $this->applyCelluleDossierLevee($odf, $idEtab, $dossierNature, $rawDossier);
            } else {
                // CC mais nature hors listes : vider le bloc cellulesPresc (port direct legacy)
                try {
                    $segCellules = $odf->setSegment('cellulesPresc');
                    $odf->mergeSegment($segCellules);
                } catch (OdfException) {
                }
            }
        }
    }

    /**
     * Persiste la pièce jointe ODT et retourne le lien HTML.
     *
     * @param array<string, string|null> $rawDossier
     * @param array<string, string|null> $rawEtabInfos
     * @param array<string, string|null> $rawEtab
     *
     * @return string lien HTML vers la pièce jointe
     */
    private function persistPieceJointe(
        Odf $odf,
        Dossier $dossier,
        string $fichierSelect,
        array $rawDossier,
        array $rawEtabInfos,
        array $rawEtab,
        int $idDossier
    ): string {
        try {
            // =====================================================================
            // CRÉATION PIÈCE JOINTE + SAUVEGARDE ODT
            // =====================================================================
            $pj = new PieceJointe();
            $pj->setNom(substr(basename($fichierSelect), 0, strlen(basename($fichierSelect)) - 4));
            $pj->setExtension('.odt');
            $pj->setDescription(sprintf(
                "Rapport de l'établissement %s (%s) généré le %s à %s",
                $rawEtabInfos['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '',
                $rawEtab['NUMEROID_ETABLISSEMENT'] ?? '',
                new \DateTime()->format('d/m/Y'),
                new \DateTime()->format('H:i')
            ));
            $pj->setDate(new \DateTime());

            $this->em->persist($pj);
            $this->em->flush();

            $dpj = new DossierPieceJointe();
            $dpj->setPieceJointe($pj);
            $dpj->setDossier($dossier);
            $dpj->setPieceJointeCommission(false);

            $this->em->persist($dpj);
            $this->em->flush();

            // Lien HTML construit AVANT la sauvegarde (comportement legacy)
            $url = $this->urlGenerator->generate('dossier_pieces_jointes_get', [
                'id' => $idDossier,
                'idpj' => $pj->getId(),
            ]);
            $libelle = htmlspecialchars($rawEtabInfos['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '', ENT_QUOTES);
            $lien = "<a href='".$url."'>Ouvrir le rapport de l'établissement : ".$libelle.'</a>';

            // Sauvegarde ODT
            if (!empty($rawDossier['ID_PLATAU'])) {
                $chemin = PathNormalizer::join($this->realDataPath, 'uploads/pieces-jointes', $pj->getId().$pj->getExtension());
            } else {
                $chemin = $this->dataStore->getFilePath($pj, 'dossier', $idDossier, true);
            }

            $this->rapportLogger->debug('Sauvegarde du fichier ODT.', [
                'dossier_id' => $idDossier,
                'chemin' => $chemin,
                'pj_id' => $pj->getId(),
            ]);

            $odf->saveToDisk($chemin);

            $this->rapportLogger->info('Rapport ODT sauvegardé avec succès.', [
                'dossier_id' => $idDossier,
                'pj_id' => $pj->getId(),
            ]);

            return $lien;
        } catch (\Exception $exception) {
            $this->rapportLogger->error('Erreur lors de la persistence de la pièce jointe.', [
                'dossier_id' => $idDossier,
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
            ]);
            throw $exception;
        }
    }

    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================

    /**
     * @param Odf|Segment $odf
     */
    private function addChamp($odf, string $nomOdf, ?string $variable): void
    {
        $this->odtWriter->setVariable($odf, $nomOdf, $variable, $this->rapportLogger);
    }

    /**
     * Remplit un segment de prescription ODT (hors CC).
     *
     * Port direct legacy addPrescription() de creationdoc.phtml.
     * Une ligne ODT par prescription : {num}, {libelle}, {textearticle}
     * (textearticle = concaténation des paires TEXTE - ARTICLE de tous les articles)
     *
     * @param array<int, array<int, array<string, mixed>>> $prescArray  Tableau issu de buildPrescriptionNestedArray()
     * @param int                                          $type        TypePrescription::*
     * @param string                                       $typeContenu 'all' | 'reprises' | 'actuelles'
     */
    private function addPrescription(Odf $odf, array $prescArray, int $type, string $typeContenu): void
    {
        $segName = PrescriptionArrayHelper::getPrescriptionSegmentName($type, $typeContenu);
        if ('' === $segName) {
            return;
        }

        try {
            $segment = $odf->setSegment($segName);
            foreach ($prescArray as $prescriptions) {
                if (empty($prescriptions)) {
                    continue;
                }

                /** @var array<string, string|null> $first */
                $first = reset($prescriptions);
                $textearticle = PrescriptionArrayHelper::buildTextearticle($prescriptions);

                $num = (string) ($first['NUM_PRESCRIPTION_DOSSIER'] ?? '');
                $libelle = ('' !== (string) ($first['LIBELLE_PRESCRIPTION_DOSSIER'] ?? ''))
                    ? (string) ($first['LIBELLE_PRESCRIPTION_DOSSIER'] ?? '')
                    : (string) ($first['PRESCRIPTIONTYPE_LIBELLE'] ?? '');
                $this->addChamp($segment, 'num', $num);
                $this->addChamp($segment, 'libelle', $libelle);
                $this->addChamp($segment, 'textearticle', $textearticle);
                $segment->merge();
            }

            $odf->mergeSegment($segment);
        } catch (OdfException) {
        }
    }

    /**
     * Remplit un segment de prescription CC (centrecommercial) dans un segment parent.
     *
     * Port direct legacy addPrescriptionCC() de creationdoc.phtml.
     * Une ligne ODT par prescription : {num}, {libelle}, {textearticle}
     *
     * @param Segment                                      $segCellule  Segment parent (cellulesPresc)
     * @param array<int, array<int, array<string, mixed>>> $prescArray  Tableau issu de buildPrescriptionNestedArray()
     * @param int                                          $type        TypePrescription::*
     * @param string                                       $typeContenu 'all' | 'reprises' | 'actuelles'
     */
    private function addPrescriptionCC(Segment $segCellule, array $prescArray, int $type, string $typeContenu): void
    {
        $segNames = [
            TypePrescription::REGLEMENTAIRE => [
                'all' => 'listeRegCellule',
                'reprises' => 'listeRegCelluleReprises',
                'actuelles' => 'listeRegCelluleActuelles',
            ],
            TypePrescription::EXPLOITATION => [
                'all' => 'listeExploitationCellule',
                'reprises' => 'listeExploitationCelluleReprises',
                'actuelles' => 'listeExploitationCelluleActuelles',
            ],
            TypePrescription::AMELIORATION => [
                'all' => 'listeAmeliorationCellule',
                'reprises' => 'listeAmeliorationCelluleReprises',
                'actuelles' => 'listeAmeliorationCelluleActuelles',
            ],
        ];
        $segName = $segNames[$type][$typeContenu] ?? '';
        if ('' === $segName) {
            return;
        }

        try {
            // @phpstan-ignore-next-line
            $segment = $segCellule->{$segName};
            if (empty($prescArray)) {
                // Port direct legacy : toujours merger le segment, même vide (sinon BEGIN/END restent verbatim)
                // @phpstan-ignore-next-line
                $this->addChamp($segment, 'num', '');
                // @phpstan-ignore-next-line
                $this->addChamp($segment, 'libelle', '');
                // @phpstan-ignore-next-line
                $this->addChamp($segment, 'textearticle', '');
                // @phpstan-ignore-next-line
                $segment->merge();
            } else {
                foreach ($prescArray as $prescriptions) {
                    if (empty($prescriptions)) {
                        continue;
                    }

                    /** @var array<string, string|null> $first */
                    $first = reset($prescriptions);
                    $textearticle = PrescriptionArrayHelper::buildTextearticle($prescriptions);

                    $num = (string) ($first['NUM_PRESCRIPTION_DOSSIER'] ?? '');
                    $libelle = ('' !== (string) ($first['LIBELLE_PRESCRIPTION_DOSSIER'] ?? ''))
                        ? (string) ($first['LIBELLE_PRESCRIPTION_DOSSIER'] ?? '')
                        : (string) ($first['PRESCRIPTIONTYPE_LIBELLE'] ?? '');
                    // @phpstan-ignore-next-line
                    $this->addChamp($segment, 'num', $num);
                    // @phpstan-ignore-next-line
                    $this->addChamp($segment, 'libelle', $libelle);
                    // @phpstan-ignore-next-line
                    $this->addChamp($segment, 'textearticle', $textearticle);
                    // @phpstan-ignore-next-line
                    $segment->merge();
                }
            }
        } catch (\Exception) {
        }
    }

    /**
     * Construit le tableau imbriqué de prescriptions depuis les entités.
     * Toutes les valeurs sont converties en string à la construction.
     *
     * @param PrescriptionDossier[] $prescriptions
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function buildPrescriptionNestedArray(array $prescriptions): array
    {
        return PrescriptionArrayHelper::buildNestedArray($prescriptions);
    }

    /**
     * Récupère les informations d'un contact (dossier d'abord, fallback établissement).
     *
     * @return array<string, string>
     */
    private function getContactInfo(int $idDossier, int $idEtab, int $idFonction): array
    {
        /** @var array<string, string> $empty */
        $empty = [
            'nom' => '', 'prenom' => '', 'mail' => '',
            'telFixe' => '', 'telPort' => '', 'numFax' => '', 'societe' => '',
        ];

        /** @var array<string, string|null>|false $rowFetch */
        $rowFetch = $this->conn->fetchAssociative(
            'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS,
                    ui.MAIL_UTILISATEURINFORMATIONS, ui.TELFIXE_UTILISATEURINFORMATIONS,
                    ui.TELPORTABLE_UTILISATEURINFORMATIONS, ui.TELFAX_UTILISATEURINFORMATIONS,
                    ui.SOCIETE_UTILISATEURINFORMATIONS
             FROM dossiercontact dc
             JOIN utilisateurinformations ui ON dc.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
             WHERE dc.ID_DOSSIER = :idDossier AND ui.ID_FONCTION = :idFonction LIMIT 1',
            ['idDossier' => $idDossier, 'idFonction' => $idFonction]
        );

        if (false === $rowFetch) {
            /** @var array<string, string|null>|false $rowFetch */
            $rowFetch = $this->conn->fetchAssociative(
                'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS,
                        ui.MAIL_UTILISATEURINFORMATIONS, ui.TELFIXE_UTILISATEURINFORMATIONS,
                        ui.TELPORTABLE_UTILISATEURINFORMATIONS, ui.TELFAX_UTILISATEURINFORMATIONS,
                        ui.SOCIETE_UTILISATEURINFORMATIONS
                 FROM etablissementcontact ec
                 JOIN utilisateurinformations ui ON ec.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                 WHERE ec.ID_ETABLISSEMENT = :idEtab AND ui.ID_FONCTION = :idFonction LIMIT 1',
                ['idEtab' => $idEtab, 'idFonction' => $idFonction]
            );
        }

        if (false === $rowFetch) {
            return $empty;
        }

        return [
            'nom' => $rowFetch['NOM_UTILISATEURINFORMATIONS'] ?? '',
            'prenom' => $rowFetch['PRENOM_UTILISATEURINFORMATIONS'] ?? '',
            'mail' => $rowFetch['MAIL_UTILISATEURINFORMATIONS'] ?? '',
            'telFixe' => $rowFetch['TELFIXE_UTILISATEURINFORMATIONS'] ?? '',
            'telPort' => $rowFetch['TELPORTABLE_UTILISATEURINFORMATIONS'] ?? '',
            'numFax' => $rowFetch['TELFAX_UTILISATEURINFORMATIONS'] ?? '',
            'societe' => $rowFetch['SOCIETE_UTILISATEURINFORMATIONS'] ?? '',
        ];
    }

    /**
     * Applique les segments cellulesPresc (branche celluleDossier — natures VP/levée CC).
     *
     * Port direct legacy : setSegment + mergeSegment TOUJOURS appelés pour vider le bloc
     * si aucune cellule trouvée (sinon les markers [!-- BEGIN/END --] restent verbatim).
     */
    private function applyCelluleDossier(Odf $odf, Dossier $dossier): void
    {
        try {
            $segCellules = $odf->setSegment('cellulesPresc');

            $affectation = $this->dossierAffectationRepository->findOneBy(['dossier' => $dossier]);
            if (null !== $affectation) {
                $dateCommission = $affectation->getDateCommission();
                if (null !== $dateCommission) {
                    $idDateCommAffect = $dateCommission->getId();
                    if (null !== $idDateCommAffect) {
                        $dossiersNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($idDateCommAffect);
                        foreach ($dossiersNonAffect as $dossCellule) {
                            $idDossCellule = (int) $dossCellule->ID_DOSSIER;
                            $libelleCellule = $dossCellule->LIBELLE_ETABLISSEMENTINFORMATIONS ?? '';

                            $this->addChamp($segCellules, 'libelleCellule', $libelleCellule);

                            if ($idDossCellule > 0) {
                                /** @var Dossier|null $dossierCellule */
                                $dossierCellule = $this->em->find(Dossier::class, $idDossCellule);
                                if (null !== $dossierCellule) {
                                    foreach ([TypePrescription::REGLEMENTAIRE, TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION] as $typeId) {
                                        $prescriptions = $this->prescriptionService->getPrescriptionsParType($dossierCellule, $typeId);
                                        $prescArray = $this->buildPrescriptionNestedArray($prescriptions);
                                        $prescArray = PrescriptionArrayHelper::withoutLevees($prescArray);
                                        $reprises = PrescriptionArrayHelper::withoutActuals($prescArray);
                                        $actuelles = PrescriptionArrayHelper::withoutPrevious($prescArray);
                                        $this->addPrescriptionCC($segCellules, $prescArray, $typeId, 'all');
                                        $this->addPrescriptionCC($segCellules, $reprises, $typeId, 'reprises');
                                        $this->addPrescriptionCC($segCellules, $actuelles, $typeId, 'actuelles');
                                    }
                                }
                            }

                            $segCellules->merge();
                        }
                    }
                }
            }

            $odf->mergeSegment($segCellules);
        } catch (OdfException) {
        }
    }

    /**
     * Applique les segments cellulesPresc (branche celluleDossierLevee — natures levée CC).
     *
     * @param array<string, string|null> $rawDossier
     */
    private function applyCelluleDossierLevee(Odf $odf, int $idEtab, int $dossierNature, array $rawDossier): void
    {
        /** @var array<int, array<string, string|null>> $enfants */
        $enfants = $this->conn->fetchAllAssociative(
            'SELECT el.ID_FILS_ETABLISSEMENT AS ID_ETABLISSEMENT, e.ID_STATUT,
                    ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementlie el
             JOIN etablissement e ON el.ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT
             LEFT JOIN etablissementinformations ei ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
             WHERE el.ID_ETABLISSEMENT = :idEtab
             ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC',
            ['idEtab' => $idEtab]
        );

        try {
            $segCellules = $odf->setSegment('cellulesPresc');
            foreach ($enfants as $enfant) {
                $idEnfant = (int) ($enfant['ID_ETABLISSEMENT'] ?? '0');
                $statutEnfant = (int) ($enfant['ID_STATUT'] ?? '0');
                if (in_array($statutEnfant, [StatutEtablissement::FERME, 99], true)) {
                    continue;
                }

                $libelleCellule = $enfant['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '';

                /** @var array<int, array<string, string|null>> $dossiersEnfant */
                $dossiersEnfant = $this->conn->fetchAllAssociative(
                    'SELECT d.ID_DOSSIER, d.DATECOMM_DOSSIER, dn.ID_NATURE AS ID_DOSSIERNATURE,
                            ei.LIBELLE_ETABLISSEMENTINFORMATIONS
                     FROM etablissementdossier ed
                     JOIN dossier d ON d.ID_DOSSIER = ed.ID_DOSSIER
                     JOIN dossiernature dn ON dn.ID_DOSSIER = d.ID_DOSSIER
                     LEFT JOIN etablissementinformations ei ON ei.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT
                     WHERE ed.ID_ETABLISSEMENT = :idEtab
                     AND d.DATESUPPRESSION_DOSSIER IS NULL
                     ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC',
                    ['idEtab' => $idEnfant]
                );

                $dateCommDossier = isset($rawDossier['DATECOMM_DOSSIER'])
                    ? substr($rawDossier['DATECOMM_DOSSIER'], 0, 10)
                    : null;

                $dossierCelluleId = null;
                foreach ($dossiersEnfant as $de) {
                    if ((int) ($de['ID_DOSSIERNATURE'] ?? '0') !== $dossierNature) {
                        continue;
                    }

                    $dateCommEnfant = substr($de['DATECOMM_DOSSIER'] ?? '', 0, 10);
                    if ($dateCommEnfant !== $dateCommDossier) {
                        continue;
                    }

                    $dossierCelluleId = (int) ($de['ID_DOSSIER'] ?? '0');
                    break;
                }

                $this->addChamp($segCellules, 'libelleCellule', $libelleCellule);

                if (null !== $dossierCelluleId && $dossierCelluleId > 0) {
                    /** @var Dossier|null $dossierCellule */
                    $dossierCellule = $this->em->find(Dossier::class, $dossierCelluleId);
                    if (null !== $dossierCellule) {
                        foreach ([TypePrescription::REGLEMENTAIRE, TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION] as $typeId) {
                            $prescriptions = $this->prescriptionService->getPrescriptionsParType($dossierCellule, $typeId);
                            $prescArray = $this->buildPrescriptionNestedArray($prescriptions);
                            $prescArray = PrescriptionArrayHelper::withoutLevees($prescArray);
                            $reprises = PrescriptionArrayHelper::withoutActuals($prescArray);
                            $actuelles = PrescriptionArrayHelper::withoutPrevious($prescArray);
                            $this->addPrescriptionCC($segCellules, $prescArray, $typeId, 'all');
                            $this->addPrescriptionCC($segCellules, $reprises, $typeId, 'reprises');
                            $this->addPrescriptionCC($segCellules, $actuelles, $typeId, 'actuelles');
                        }
                    }
                } else {
                    foreach ([TypePrescription::REGLEMENTAIRE, TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION] as $typeId) {
                        $this->addPrescriptionCC($segCellules, [], $typeId, 'all');
                        $this->addPrescriptionCC($segCellules, [], $typeId, 'reprises');
                        $this->addPrescriptionCC($segCellules, [], $typeId, 'actuelles');
                    }
                }

                $segCellules->merge();
            }

            $odf->mergeSegment($segCellules);
        } catch (OdfException) {
        }
    }

    /**
     * Applique les segments avisDerogations ou avisDerogationsEtablissement.
     *
     * @param bool $isEtablissement Si true, récupère par établissement (DISPLAY_HISTORIQUE=1)
     */
    private function applyAvisDerogations(Odf $odf, int $idDossier, string $segmentName, bool $isEtablissement, int $idEtab): void
    {
        try {
            $seg = $odf->setSegment($segmentName);
            if ($isEtablissement) {
                // Batch : parent + enfants en une seule requête (évite le N+1, port du fix legacy 986f23f).
                $etabIds = [$idEtab];
                /** @var array<int, array<string, string|null>> $enfantsFetch */
                $enfantsFetch = $this->conn->fetchAllAssociative(
                    'SELECT ID_FILS_ETABLISSEMENT FROM etablissementlie WHERE ID_ETABLISSEMENT = :id',
                    ['id' => $idEtab]
                );
                foreach ($enfantsFetch as $enfant) {
                    $etabIds[] = (int) ($enfant['ID_FILS_ETABLISSEMENT'] ?? '0');
                }

                /** @var array<int, array<string, string|null>> $rows */
                $rows = $this->conn->fetchAllAssociative(
                    'SELECT ad.TYPE, ad.TITRE, ad.OBJET, ad.JUSTIFICATION, ad.ARTICLES_CONCERNES,
                            ad.MESURES_COMPENSATOIRES_PROPOSEES, ad.ANALYSE_DE_RISQUE, a.LIBELLE_AVIS,
                            ad.ID_DOSSIER_LIE, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER
                     FROM avisderogations ad
                     JOIN dossier d ON ad.ID_DOSSIER = d.ID_DOSSIER
                     JOIN avis a ON ad.AVIS = a.ID_AVIS
                     JOIN etablissementdossier ed ON d.ID_DOSSIER = ed.ID_DOSSIER
                     WHERE ed.ID_ETABLISSEMENT IN (?)
                     AND ad.DISPLAY_HISTORIQUE = 1',
                    [$etabIds],
                    [ArrayParameterType::INTEGER]
                );
            } else {
                /** @var array<int, array<string, string|null>> $rows */
                $rows = $this->conn->fetchAllAssociative(
                    'SELECT ad.TYPE, ad.TITRE, ad.OBJET, ad.JUSTIFICATION, ad.ARTICLES_CONCERNES,
                            ad.MESURES_COMPENSATOIRES_PROPOSEES, ad.ANALYSE_DE_RISQUE, a.LIBELLE_AVIS,
                            ad.ID_DOSSIER_LIE, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER
                     FROM avisderogations ad
                     JOIN dossier d ON ad.ID_DOSSIER = d.ID_DOSSIER
                     JOIN avis a ON ad.AVIS = a.ID_AVIS
                     WHERE ad.ID_DOSSIER = :idDossier',
                    ['idDossier' => $idDossier]
                );
            }

            foreach ($rows as $row) {
                $this->addChamp($seg, 'type', $row['TYPE'] ?? '');
                $this->addChamp($seg, 'titre', $row['TITRE'] ?? '');
                $this->addChamp($seg, 'objet', $row['OBJET'] ?? '');
                $this->addChamp($seg, 'justification', $row['JUSTIFICATION'] ?? '');
                $this->addChamp($seg, 'articles_concernes', $row['ARTICLES_CONCERNES'] ?? '');
                $this->addChamp($seg, 'mesures_compensatoires_proposees', $row['MESURES_COMPENSATOIRES_PROPOSEES'] ?? '');
                $this->addChamp($seg, 'analyse_de_risque', $row['ANALYSE_DE_RISQUE'] ?? '');
                $this->addChamp($seg, 'avis', $row['LIBELLE_AVIS'] ?? '');
                $this->addChamp($seg, 'identifiantdossierleve', $row['ID_DOSSIER_LIE'] ?? '');
                $dateSource = !empty($row['DATECOMM_DOSSIER']) ? $row['DATECOMM_DOSSIER'] : ($row['DATEVISITE_DOSSIER'] ?? null);
                $this->addChamp($seg, 'date_avis_derogation', DateFormatterFr::formatShort($dateSource));
                $seg->merge();
            }

            $odf->mergeSegment($seg);
        } catch (OdfException) {
        }
    }

    private function applyFormulaires(Odf $odf, Dossier $dossier, Etablissement $etablissement): void
    {
        $capsules = [
            NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES => $this->descriptifService->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES),
            NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT => $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT),
            NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER => $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER),
            NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT => $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT),
        ];

        $this->odtWriter->applyFormulairesToOdf($odf, $capsules);
    }
}
