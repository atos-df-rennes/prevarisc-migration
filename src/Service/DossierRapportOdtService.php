<?php

namespace App\Service;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Constant\NomInterneCapsule;
use App\Constant\StatutEtablissement;
use App\Constant\TypeActivite;
use App\Constant\TypeChamp;
use App\Constant\TypePrescription;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use App\Entity\Etablissement;
use App\Entity\PieceJointe;
use App\Entity\PrescriptionDossier;
use App\Entity\Rubrique;
use App\Entity\Valeur;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use App\Repository\ListeDocumentConsulteRepository;
use App\Service\DataStore\DataStoreFactory;
use App\Service\Formulaires\Fusion\ChampNamer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Port direct de creationdocAction() (DossierController) + creationdoc.phtml.
 *
 * Génère un rapport ODT pour un dossier/établissement et retourne un lien HTML
 * vers la pièce jointe nouvellement créée.
 */
class DossierRapportOdtService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var Connection */
    private $conn;

    /** @var DataStoreFactory */
    private $dataStoreFactory;

    /** @var DossierPrescriptionService */
    private $prescriptionService;

    /** @var DossierAffectationRepository */
    private $dossierAffectationRepository;

    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    /** @var Descriptif */
    private $descriptifService;

    /** @var ListeDocumentConsulteRepository */
    private $listeDocRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var string */
    private $realDataPath;

    /** @var bool */
    private $debugEnabled;

    /** @var resource|null */
    private $debugFile;

    public function __construct(
        EntityManagerInterface $em,
        Connection $conn,
        DataStoreFactory $dataStoreFactory,
        DossierPrescriptionService $prescriptionService,
        DossierAffectationRepository $dossierAffectationRepository,
        DateCommissionRepository $dateCommissionRepository,
        Descriptif $descriptifService,
        ListeDocumentConsulteRepository $listeDocRepository,
        DossierRepository $dossierRepository,
        UrlGeneratorInterface $urlGenerator,
        string $realDataPath,
        bool $debugEnabled
    ) {
        $this->em = $em;
        $this->conn = $conn;
        $this->dataStoreFactory = $dataStoreFactory;
        $this->prescriptionService = $prescriptionService;
        $this->dossierAffectationRepository = $dossierAffectationRepository;
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->descriptifService = $descriptifService;
        $this->listeDocRepository = $listeDocRepository;
        $this->dossierRepository = $dossierRepository;
        $this->urlGenerator = $urlGenerator;
        $this->realDataPath = $realDataPath;
        $this->debugEnabled = $debugEnabled;
        $this->debugFile = null;
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

        // Initialisation du debug (port direct de creationdoc.phtml lignes 34-42)
        $this->debugFile = null;
        if ($this->debugEnabled) {
            $debugFileName = 'rapport_dossier_'.$idDossier.'.txt';
            $debugFileHandle = @fopen(sys_get_temp_dir().DIRECTORY_SEPARATOR.$debugFileName, 'w');
            $this->debugFile = false !== $debugFileHandle ? $debugFileHandle : null;
        }

        // --- Init Odf ---
        $odf = new \Odf($fichierSelect);

        // --- dateDuJour ---
        $this->addChamp($odf, 'dateDuJour', (new \DateTime())->format('d/m/Y'));

        // --- Type et nature dossier ---
        /** @var array<string, string|null>|false $typeDossierRow */
        $typeDossierRow = $this->dossierRepository->getTypeDossier($idDossier);
        $typeDossierId = false !== $typeDossierRow ? ($typeDossierRow['TYPE_DOSSIER'] ?? null) : null;
        $typeDossier = '';
        if (null !== $typeDossierId) {
            /** @var array<string, string|null>|false $typeDossierLabelFetch */
            $typeDossierLabelFetch = $this->conn->fetchAssociative(
                'SELECT LIBELLE_DOSSIERTYPE FROM dossiertype WHERE ID_DOSSIERTYPE = :id',
                ['id' => $typeDossierId]
            );
            $typeDossier = false !== $typeDossierLabelFetch ? ($typeDossierLabelFetch['LIBELLE_DOSSIERTYPE'] ?? '') : '';
        }

        $this->addChamp($odf, 'typeDossier', $typeDossier);

        $dossierNature = $this->dossierRepository->getDossierNatureId($idDossier);
        $natureDossier = '';
        if (null !== $dossierNature) {
            /** @var array<string, string|null>|false $natureRowFetch */
            // Port direct de Model_DbTable_DossierNature::getDossierNatureLibelle()
            // La table `nature` n'existe pas — la jointure est dossiernature → dossiernatureliste
            $natureRowFetch = $this->conn->fetchAssociative(
                'SELECT LIBELLE_DOSSIERNATURE FROM dossiernatureliste WHERE ID_DOSSIERNATURE = :id',
                ['id' => $dossierNature]
            );
            if (false !== $natureRowFetch) {
                $natureDossier = $natureRowFetch['LIBELLE_DOSSIERNATURE'] ?? '';
            }
        }

        $this->addChamp($odf, 'natureDossier', $natureDossier);

        // --- Commission ---
        $commissionInfos = 'Aucune commission';
        if (!empty($rawDossier['COMMISSION_DOSSIER'])) {
            /** @var array<string, string|null>|false $commRowFetch */
            $commRowFetch = $this->conn->fetchAssociative(
                'SELECT LIBELLE_COMMISSION FROM commission WHERE ID_COMMISSION = :id',
                ['id' => $rawDossier['COMMISSION_DOSSIER']]
            );
            if (false !== $commRowFetch && !empty($commRowFetch['LIBELLE_COMMISSION'])) {
                $commissionInfos = $commRowFetch['LIBELLE_COMMISSION'];
            }
        }

        $this->addChamp($odf, 'libelle_commission', $commissionInfos);

        // --- dateCommEntete ---
        $dateCommEntete = 'Indisponible';
        if (!empty($rawDossier['DATECOMM_DOSSIER'])) {
            $dtComm = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DATECOMM_DOSSIER'], 0, 10));
            if ($dtComm instanceof \DateTime) {
                $dateCommEntete = $dtComm->format('d/m/Y');
            }
        }

        $this->addChamp($odf, 'date_commEntete', $dateCommEntete);

        // --- numWinPrev ---
        $this->addChamp($odf, 'numWinPrev', $rawEtab['NUMEROID_ETABLISSEMENT'] ?? '');

        // --- objetDossier ---
        $this->addChamp($odf, 'objetDossier', $rawDossier['OBJET_DOSSIER'] ?? '');

        // --- analyseRisque ---
        $this->addChamp($odf, 'analyseRisque', $rawDossier['ANALYSERISQUE_DOSSIER'] ?? '');

        // --- observationDossier ---
        $this->addChamp($odf, 'observationDossier', $rawDossier['OBSERVATION_DOSSIER'] ?? '');

        // --- descriptifDossier ---
        $this->addChamp($odf, 'descriptifDossier', $rawDossier['DESCRIPTIF_DOSSIER'] ?? '');

        // --- criticite ---
        $this->addChamp($odf, 'criticite', $rawDossier['CRITICITE_DOSSIER'] ?? '');

        // --- dateRvrat ---
        $dateRvrat = 'Indisponible';
        if (!empty($rawDossier['DATERVRAT_DOSSIER'])) {
            $dtRvrat = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DATERVRAT_DOSSIER'], 0, 10));
            if ($dtRvrat instanceof \DateTime) {
                $dateRvrat = $this->formatDateLongFr($dtRvrat);
            }
        }

        $this->addChamp($odf, 'dateRvrat', $dateRvrat);

        // --- dateDelaipresc ---
        $dateDelaipresc = 'Pas de date';
        if (!empty($rawDossier['DATEDELAIPRESC_DOSSIER'])) {
            $dtDelai = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DATEDELAIPRESC_DOSSIER'], 0, 10));
            if ($dtDelai instanceof \DateTime) {
                $dateDelaipresc = $this->formatDateLongFr($dtDelai);
            }
        }

        $this->addChamp($odf, 'dateDelaipresc', $dateDelaipresc);

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

        // --- dateVisite / heureVisite ---
        $dateVisite = '';
        $heureVisite = '';
        if (!empty($rawDossier['DATEVISITE_DOSSIER'])) {
            $dtVisite = \DateTime::createFromFormat('Y-m-d H:i:s', $rawDossier['DATEVISITE_DOSSIER']);
            if (!$dtVisite instanceof \DateTime) {
                $dtVisite = \DateTime::createFromFormat('Y-m-d', substr($rawDossier['DATEVISITE_DOSSIER'], 0, 10));
            }

            if ($dtVisite instanceof \DateTime) {
                $dateVisite = $this->formatDateLongFr($dtVisite);
                $heureVisite = $dtVisite->format('H:i');
            }
        }

        $this->addChamp($odf, 'dateVisite', $dateVisite);
        $this->addChamp($odf, 'heureVisite', $heureVisite);

        // --- dateGeneration ---
        $this->addChamp($odf, 'dateGeneration', $this->formatDateLongFr(new \DateTime()));

        // --- dateComm ---
        $this->addChamp($odf, 'dateComm', $this->formatDateLongFrFromStr($rawDossier['DATECOMM_DOSSIER'] ?? null));

        // --- dateIncomplet ---
        $this->addChamp($odf, 'dateIncomplet', $this->formatDateLongFrFromStr($rawDossier['DATEINCOMPLET_DOSSIER'] ?? null));

        // --- demandeurDossier ---
        $this->addChamp($odf, 'demandeurDossier', $rawDossier['DEMANDEUR_DOSSIER'] ?? '');

        // --- adresse établissement (commune/numInsee) ---
        /** @var array<string, string|null>|false $adresseRowFetch */
        $adresseRowFetch = $this->conn->fetchAssociative(
            'SELECT ea.NUMERO_ADRESSE, ar.LIBELLE_RUE, ac.LIBELLE_COMMUNE, ac.CODEPOSTAL_COMMUNE, ac.NUMINSEE_COMMUNE
             FROM etablissementadresse ea
             JOIN adresserue ar ON ea.ID_RUE = ar.ID_RUE
             JOIN adressecommune ac ON ea.NUMINSEE_COMMUNE = ac.NUMINSEE_COMMUNE
             WHERE ea.ID_ETABLISSEMENT = :id LIMIT 1',
            ['id' => $idEtab]
        );
        /** @var array<string, string|null> $adresseRow */
        $adresseRow = false !== $adresseRowFetch ? $adresseRowFetch : [];
        $numInsee = $adresseRow['NUMINSEE_COMMUNE'] ?? null;

        // --- servInstructeur ---
        $servInstructeur = '';
        $servInstructeurPrenomContact = '';
        $servInstructeurNomContact = '';
        $servInstructeurMail = '';
        if (!empty($rawDossier['SERVINSTRUCTEUR_DOSSIER'])) {
            $servInstructeur = $rawDossier['SERVINSTRUCTEUR_DOSSIER'];
            /** @var array<string, string|null>|false $groupRowFetch */
            $groupRowFetch = $this->conn->fetchAssociative(
                'SELECT g.LIBELLE_GROUPEMENT, ui.PRENOM_UTILISATEURINFORMATIONS, ui.NOM_UTILISATEURINFORMATIONS, ui.MAIL_UTILISATEURINFORMATIONS
                 FROM groupement g
                 INNER JOIN groupementtype gt ON g.ID_GROUPEMENTTYPE = gt.ID_GROUPEMENTTYPE
                 LEFT JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = g.ID_UTILISATEURINFORMATIONS
                 WHERE g.LIBELLE_GROUPEMENT = :libelle LIMIT 1',
                ['libelle' => $rawDossier['SERVINSTRUCTEUR_DOSSIER']]
            );
            if (false !== $groupRowFetch) {
                $servInstructeurPrenomContact = $groupRowFetch['PRENOM_UTILISATEURINFORMATIONS'] ?? '';
                $servInstructeurNomContact = $groupRowFetch['NOM_UTILISATEURINFORMATIONS'] ?? '';
                $servInstructeurMail = $groupRowFetch['MAIL_UTILISATEURINFORMATIONS'] ?? '';
            } elseif (null !== $numInsee) {
                /** @var array<string, string|null>|false $communeRowFetch */
                $communeRowFetch = $this->conn->fetchAssociative(
                    'SELECT ui.PRENOM_UTILISATEURINFORMATIONS, ui.NOM_UTILISATEURINFORMATIONS, ui.MAIL_UTILISATEURINFORMATIONS
                     FROM adressecommune ac
                     JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = ac.ID_UTILISATEURINFORMATIONS
                     WHERE ac.NUMINSEE_COMMUNE = :numInsee LIMIT 1',
                    ['numInsee' => $numInsee]
                );
                if (false !== $communeRowFetch) {
                    $servInstructeurPrenomContact = $communeRowFetch['PRENOM_UTILISATEURINFORMATIONS'] ?? '';
                    $servInstructeurNomContact = $communeRowFetch['NOM_UTILISATEURINFORMATIONS'] ?? '';
                    $servInstructeurMail = $communeRowFetch['MAIL_UTILISATEURINFORMATIONS'] ?? '';
                }
            }
        }

        $this->addChamp($odf, 'servInstructeur', $servInstructeur);
        $this->addChamp($odf, 'servInstructeurPrenomContact', $servInstructeurPrenomContact);
        $this->addChamp($odf, 'servInstructeurNomContact', $servInstructeurNomContact);
        $this->addChamp($odf, 'servInstructeurMail', $servInstructeurMail);

        // --- Dérogations dossier ---
        $this->addChamp($odf, 'reglesDerog', $rawDossier['REGLES_DOSSIER'] ?? '');
        $this->addChamp($odf, 'justificationDerog', $rawDossier['JUSTIFICATION_DOSSIER'] ?? '');
        $this->addChamp($odf, 'mesuresCompensatoiresDerog', $rawDossier['MESURESCOMPENSATOIRES_DOSSIER'] ?? '');
        $this->addChamp($odf, 'mesuresComplementairesDerog', $rawDossier['MESURESCOMPLEMENTAIRES_DOSSIER'] ?? '');

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
        } catch (\OdfException $odfException) {
        }

        try {
            $segPrevTel = $odf->setSegment('preventionnistesTel');
            foreach ($preventionnisteRows as $prev) {
                $this->addChamp($segPrevTel, 'tel', $prev['TELFIXE_UTILISATEURINFORMATIONS'] ?? '');
                $segPrevTel->merge();
            }

            $odf->mergeSegment($segPrevTel);
        } catch (\OdfException $odfException) {
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
                if (!empty($consulte['DATE_CONSULTE']) && '0000-00-00' !== substr($consulte['DATE_CONSULTE'], 0, 10)) {
                    $dtDc = \DateTime::createFromFormat('Y-m-d', substr($consulte['DATE_CONSULTE'], 0, 10));
                    if ($dtDc instanceof \DateTime) {
                        $dateDc = $dtDc->format('d/m/Y');
                    }
                }

                $this->addChamp($segListeDocs, 'datedoc', $dateDc);
                $segListeDocs->merge();
            }

            $odf->mergeSegment($segListeDocs);
        } catch (\OdfException $odfException) {
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
        } catch (\OdfException $odfException) {
        }

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
            $prescArray = $this->withoutLevees($prescArray);
            $reprises = $this->withoutActuals($prescArray);
            $actuelles = $this->withoutPrevious($prescArray);
            $this->addPrescription($odf, $prescArray, $typeId, 'all');
            $this->addPrescription($odf, $reprises, $typeId, 'reprises');
            $this->addPrescription($odf, $actuelles, $typeId, 'actuelles');
        }

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
        } catch (\OdfException $odfException) {
        }

        // =====================================================================
        // CELLULES CC
        // Port direct legacy : if ($this->id_typeactivite == 29) — le bloc cellulesPresc
        // est TOUJOURS traité (setSegment + mergeSegment) pour les CC, quelle que soit
        // la nature du dossier, afin d'éviter les markers [!-- BEGIN/END --] verbatim.
        // =====================================================================
        $idTypeActivite = (int) ($rawEtabInfos['ID_TYPEACTIVITE'] ?? 0);
        if (TypeActivite::CENTRE_COMMERCIAL === $idTypeActivite && !$repriseCC) {
            if (null !== $dossierNature && in_array($dossierNature, DossierNature::NATURES_CC, true)) {
                $this->applyCelluleDossier($odf, $dossier);
            } elseif (null !== $dossierNature && in_array($dossierNature, DossierNature::LEVEES, true)) {
                $this->applyCelluleDossierLevee($odf, $idEtab, $dossierNature, $rawDossier);
            } else {
                // CC mais nature hors listes : vider le bloc cellulesPresc (port direct legacy)
                try {
                    $segCellules = $odf->setSegment('cellulesPresc');
                    $odf->mergeSegment($segCellules);
                } catch (\OdfException $odfException) {
                }
            }
        }

        // =====================================================================
        // DATES
        // =====================================================================
        $this->addChamp($odf, 'dateDepotMairie', $this->formatDateShortFr($rawDossier['DATEMAIRIE_DOSSIER'] ?? null));
        $this->addChamp($odf, 'dateDepotSec', $this->formatDateShortFr($rawDossier['DATESECRETARIAT_DOSSIER'] ?? null));
        $this->addChamp($odf, 'dateSDIS', $this->formatDateShortFr($rawDossier['DATESDIS_DOSSIER'] ?? null));
        $this->addChamp($odf, 'dateCreationDossier', $this->formatDateShortFr($rawDossier['DATEINSERT_DOSSIER'] ?? null));

        // =====================================================================
        // ÉTABLISSEMENT — informations générales
        // =====================================================================
        $this->addChamp($odf, 'genreEtab', $rawEtabInfos['LIBELLE_GENRE'] ?? '');
        $this->addChamp($odf, 'etablissementLibelle', $rawEtabInfos['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'typeLettreP', $rawEtabInfos['LIBELLE_TYPE'] ?? '');
        $this->addChamp($odf, 'activiteP', $rawEtabInfos['LIBELLE_ACTIVITE'] ?? '');

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
        $libelleCat = $rawEtabInfos['LIBELLE_CATEGORIE'] ?? '';
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
        $avisDossierRow = null;
        if (!empty($rawDossier['AVIS_DOSSIER'])) {
            /** @var array<string, string|null>|false $avisDossierRowFetch */
            $avisDossierRowFetch = $this->conn->fetchAssociative(
                'SELECT LIBELLE_AVIS FROM avis WHERE ID_AVIS = :id',
                ['id' => $rawDossier['AVIS_DOSSIER']]
            );
            $avisDossierRow = false !== $avisDossierRowFetch ? $avisDossierRowFetch : null;
        }

        $this->addChamp($odf, 'avisDossier', null !== $avisDossierRow ? ($avisDossierRow['LIBELLE_AVIS'] ?? '') : '');

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
                $dateLastVP = $this->formatDateShortFr($lastVpRowFetch['DATEVISITE_DOSSIER'] ?? null);
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

        // --- Maire ---
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

        // =====================================================================
        // GN (Groupement de Nuit)
        // =====================================================================
        // GN dérivé de ID_GENRE (2 = établissement, 3 = cellule) — la colonne
        // GN_ETABLISSEMENTINFORMATIONS du legacy n'existe pas dans la base migrée.
        $gn = (int) ($rawEtabInfos['ID_GENRE'] ?? '0');
        if (2 === $gn) {
            $this->addChamp($odf, 'gn2', '×');
            $this->addChamp($odf, 'gn3', '');
        } elseif (3 === $gn) {
            $this->addChamp($odf, 'gn2', '');
            $this->addChamp($odf, 'gn3', '×');
        }

        // =====================================================================
        // Effectifs/dégagements (non portés — pas d'équivalent migré)
        // =====================================================================
        $this->addChamp($odf, 'descriptionEffectifDossier', null);
        $this->addChamp($odf, 'descriptionDegagementDossier', null);
        $this->addChamp($odf, 'descriptionEffectifEtablissement', null);
        $this->addChamp($odf, 'descriptionDegagementEtablissement', null);

        // =====================================================================
        // SEGMENTS : avisDerogations + avisDerogationsEtablissement
        // =====================================================================
        $this->applyAvisDerogations($odf, $idDossier, 'avisDerogations', false, $idEtab);
        $this->applyAvisDerogations($odf, $idDossier, 'avisDerogationsEtablissement', true, $idEtab);

        // =====================================================================
        // FORMULAIRES (capsules rubriques)
        // =====================================================================
        $this->applyFormulaires($odf, $dossier, $etablissement);

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
            (new \DateTime())->format('d/m/Y'),
            (new \DateTime())->format('H:i')
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
        $lien = "<a href='".$url."'>Ouvrir le rapport de l'établissement : ".$libelle.'<a/>';

        // Sauvegarde ODT
        if (!empty($rawDossier['ID_PLATAU'])) {
            $chemin = $this->realDataPath.'/uploads/pieces-jointes/'.$pj->getId().$pj->getExtension();
        } else {
            $chemin = $this->dataStoreFactory->create()->getFilePath($pj, 'dossier', $idDossier, true);
        }

        $odf->saveToDisk($chemin);

        // Fermeture debug
        if (is_resource($this->debugFile)) {
            fclose($this->debugFile);
            $this->debugFile = null;
        }

        return $lien;
    }

    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================

    /**
     * Wrapper autour de Odf::setVars() avec écriture dans le debug file.
     *
     * @param \Odf|\Segment $odf
     */
    private function addChamp($odf, string $nomOdf, ?string $variable): void
    {
        try {
            $odf->setVars($nomOdf, $variable ?? '', true, 'UTF8');
            if (is_resource($this->debugFile)) {
                $prefix = $odf instanceof \Segment ? '['.$odf->getName().'] ' : '';
                fwrite($this->debugFile, $prefix.'{'.$nomOdf.'} : '.json_encode($variable, JSON_UNESCAPED_UNICODE)."\n");
            }
        } catch (\Exception $exception) {
            // Silencieux — variable absente du template ODT
        }
    }

    /**
     * Retourne le nom du segment ODT pour un type de prescription et un contenu donné.
     *
     * @param int    $type        TypePrescription::*
     * @param string $typeContenu 'all' | 'reprises' | 'actuelles'
     */
    private function getPrescriptionSegmentName(int $type, string $typeContenu): string
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
     * Remplit un segment de prescription ODT (hors CC).
     *
     * Port direct legacy addPrescription() de creationdoc.phtml.
     * Une ligne ODT par prescription : {num}, {libelle}, {textearticle}
     * (textearticle = concaténation des paires TEXTE - ARTICLE de tous les articles)
     *
     * @param array<int, array<int, array<string, string>>> $prescArray  Tableau issu de buildPrescriptionNestedArray()
     * @param int                                           $type        TypePrescription::*
     * @param string                                        $typeContenu 'all' | 'reprises' | 'actuelles'
     */
    private function addPrescription(\Odf $odf, array $prescArray, int $type, string $typeContenu): void
    {
        $segName = $this->getPrescriptionSegmentName($type, $typeContenu);
        if ('' === $segName) {
            return;
        }

        try {
            $segment = $odf->setSegment($segName);
            foreach ($prescArray as $prescriptions) {
                if (empty($prescriptions)) {
                    continue;
                }

                $first = reset($prescriptions);
                $nbAssoc = count($prescriptions);
                $nbCpt = $nbAssoc;
                $textearticle = '';
                foreach ($prescriptions as $assoc) {
                    $texteT = $assoc['LIBELLE_TEXTE'] ?? '';
                    $articleT = $assoc['LIBELLE_ARTICLE'] ?? '';
                    $textearticle .= ('' !== $texteT ? $texteT.' - ' : ' - ');
                    if ($nbCpt > 1) {
                        $textearticle .= ('' !== $articleT ? $articleT.', ' : ', ');
                    } else {
                        $textearticle .= ('' !== $articleT ? $articleT.' ' : ' ');
                    }

                    --$nbCpt;
                }

                $num = $first['NUM_PRESCRIPTION_DOSSIER'];
                $libelle = ('' !== $first['LIBELLE_PRESCRIPTION_DOSSIER'])
                    ? $first['LIBELLE_PRESCRIPTION_DOSSIER']
                    : $first['PRESCRIPTIONTYPE_LIBELLE'];
                $this->addChamp($segment, 'num', $num);
                $this->addChamp($segment, 'libelle', $libelle);
                $this->addChamp($segment, 'textearticle', $textearticle);
                $segment->merge();
            }

            $odf->mergeSegment($segment);
        } catch (\OdfException $odfException) {
        }
    }

    /**
     * Remplit un segment de prescription CC (centrecommercial) dans un segment parent.
     *
     * Port direct legacy addPrescriptionCC() de creationdoc.phtml.
     * Une ligne ODT par prescription : {num}, {libelle}, {textearticle}
     *
     * @param \Segment                                      $segCellule  Segment parent (cellulesPresc)
     * @param array<int, array<int, array<string, string>>> $prescArray  Tableau issu de buildPrescriptionNestedArray()
     * @param int                                           $type        TypePrescription::*
     * @param string                                        $typeContenu 'all' | 'reprises' | 'actuelles'
     */
    private function addPrescriptionCC($segCellule, array $prescArray, int $type, string $typeContenu): void
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

                    $first = reset($prescriptions);
                    $nbAssoc = count($prescriptions);
                    $nbCpt = $nbAssoc;
                    $textearticle = '';
                    foreach ($prescriptions as $assoc) {
                        $texteT = $assoc['LIBELLE_TEXTE'] ?? '';
                        $articleT = $assoc['LIBELLE_ARTICLE'] ?? '';
                        $textearticle .= ('' !== $texteT ? $texteT.' - ' : ' - ');
                        if ($nbCpt > 1) {
                            $textearticle .= ('' !== $articleT ? $articleT.', ' : ', ');
                        } else {
                            $textearticle .= ('' !== $articleT ? $articleT.' ' : ' ');
                        }

                        --$nbCpt;
                    }

                    $num = $first['NUM_PRESCRIPTION_DOSSIER'];
                    $libelle = ('' !== $first['LIBELLE_PRESCRIPTION_DOSSIER'])
                        ? $first['LIBELLE_PRESCRIPTION_DOSSIER']
                        : $first['PRESCRIPTIONTYPE_LIBELLE'];
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
        } catch (\Exception $exception) {
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

    /**
     * Supprime les prescriptions ayant une date de levée (DATE_LEVEE non vide).
     *
     * Port direct legacy Service_Dossier::withoutLevees().
     *
     * @param array<int, array<int, array<string, string>>> $prescArray
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function withoutLevees(array $prescArray): array
    {
        $result = [];
        foreach ($prescArray as $i => $prescriptions) {
            foreach ($prescriptions as $j => $assoc) {
                if ('' === $assoc['DATE_LEVEE']) {
                    $result[$i][$j] = $assoc;
                }
            }
        }

        return $result;
    }

    /**
     * Supprime les entrées sans reprise (ID_DOSSIER_REPRISE vide = actuelles seules).
     *
     * @param array<int, array<int, array<string, string>>> $prescArray
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function withoutActuals(array $prescArray): array
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
     * @param array<int, array<int, array<string, string>>> $prescArray
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function withoutPrevious(array $prescArray): array
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
    private function applyCelluleDossier(\Odf $odf, Dossier $dossier): void
    {
        try {
            $segCellules = $odf->setSegment('cellulesPresc');

            $affectation = $this->dossierAffectationRepository->findOneBy(['dossier' => $dossier]);
            if (null !== $affectation) {
                $dateCommission = $affectation->getDateCommission();
                if (null !== $dateCommission) {
                    $idDateCommAffect = $dateCommission->getId();
                    if (null !== $idDateCommAffect) {
                        /** @var array<int, array<string, string|null>> $dossiersNonAffect */
                        $dossiersNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($idDateCommAffect);
                        foreach ($dossiersNonAffect as $dossCellule) {
                            $idDossCellule = isset($dossCellule['ID_DOSSIER']) ? (int) $dossCellule['ID_DOSSIER'] : 0;
                            $libelleCellule = $dossCellule['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '';

                            $this->addChamp($segCellules, 'libelleCellule', $libelleCellule);

                            if ($idDossCellule > 0) {
                                /** @var Dossier|null $dossierCellule */
                                $dossierCellule = $this->em->find(Dossier::class, $idDossCellule);
                                if (null !== $dossierCellule) {
                                    foreach ([TypePrescription::REGLEMENTAIRE, TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION] as $typeId) {
                                        $prescriptions = $this->prescriptionService->getPrescriptionsParType($dossierCellule, $typeId);
                                        $prescArray = $this->buildPrescriptionNestedArray($prescriptions);
                                        $prescArray = $this->withoutLevees($prescArray);
                                        $reprises = $this->withoutActuals($prescArray);
                                        $actuelles = $this->withoutPrevious($prescArray);
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
        } catch (\OdfException $odfException) {
        }
    }

    /**
     * Applique les segments cellulesPresc (branche celluleDossierLevee — natures levée CC).
     *
     * @param array<string, string|null> $rawDossier
     */
    private function applyCelluleDossierLevee(\Odf $odf, int $idEtab, int $dossierNature, array $rawDossier): void
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
                            $prescArray = $this->withoutLevees($prescArray);
                            $reprises = $this->withoutActuals($prescArray);
                            $actuelles = $this->withoutPrevious($prescArray);
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
        } catch (\OdfException $odfException) {
        }
    }

    /**
     * Applique les segments avisDerogations ou avisDerogationsEtablissement.
     *
     * @param bool $isEtablissement Si true, récupère par établissement (DISPLAY_HISTORIQUE=1)
     */
    private function applyAvisDerogations(\Odf $odf, int $idDossier, string $segmentName, bool $isEtablissement, int $idEtab): void
    {
        try {
            $seg = $odf->setSegment($segmentName);

            /** @var array<int, array<string, string|null>> $rows */
            $rows = [];
            if ($isEtablissement) {
                $etabIds = [$idEtab];
                /** @var array<int, array<string, string|null>> $enfantsFetch */
                $enfantsFetch = $this->conn->fetchAllAssociative(
                    'SELECT ID_FILS_ETABLISSEMENT FROM etablissementlie WHERE ID_ETABLISSEMENT = :id',
                    ['id' => $idEtab]
                );
                foreach ($enfantsFetch as $enfant) {
                    $etabIds[] = (int) ($enfant['ID_FILS_ETABLISSEMENT'] ?? '0');
                }

                foreach ($etabIds as $etabId) {
                    /** @var array<int, array<string, string|null>> $etabRows */
                    $etabRows = $this->conn->fetchAllAssociative(
                        'SELECT ad.TYPE, ad.TITRE, ad.OBJET, ad.JUSTIFICATION, ad.ARTICLES_CONCERNES,
                                ad.MESURES_COMPENSATOIRES_PROPOSEES, ad.ANALYSE_DE_RISQUE, a.LIBELLE_AVIS,
                                ad.ID_DOSSIER_LIE, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER
                         FROM avisderogations ad
                         JOIN dossier d ON ad.ID_DOSSIER = d.ID_DOSSIER
                         JOIN avis a ON ad.AVIS = a.ID_AVIS
                         JOIN etablissementdossier ed ON d.ID_DOSSIER = ed.ID_DOSSIER
                         WHERE ed.ID_ETABLISSEMENT = :idEtab
                         AND ad.DISPLAY_HISTORIQUE = 1',
                        ['idEtab' => $etabId]
                    );
                    foreach ($etabRows as $r) {
                        $rows[] = $r;
                    }
                }
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
                $this->addChamp($seg, 'date_avis_derogation', $this->formatDateShortFr($dateSource));
                $seg->merge();
            }

            $odf->mergeSegment($seg);
        } catch (\OdfException $odfException) {
        }
    }

    /**
     * Applique les formulaires (capsules rubriques) dans le document ODT.
     *
     * Port direct de la boucle formulaires de creationdoc.phtml.
     */
    private function applyFormulaires(\Odf $odf, Dossier $dossier, Etablissement $etablissement): void
    {
        $capsules = [
            NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES => $this->descriptifService->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES),
            NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT => $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT),
            NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER => $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER),
            NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT => $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT),
        ];

        foreach ($capsules as $nomInterne => $data) {
            if (null === $data['capsule']) {
                continue;
            }

            foreach ($data['rubriques'] as $rubriqueData) {
                /** @var Rubrique $rubrique */
                $rubrique = $rubriqueData['rubrique'];
                foreach ($rubriqueData['champs'] as $champData) {
                    /** @var Champ $champ */
                    $champ = $champData['champ'];
                    $typeId = null !== $champ->getType() ? $champ->getType()->getId() : null;

                    if (TypeChamp::PARENT === $typeId) {
                        if ($champ->getTableau()) {
                            // Branche tableau : en-têtes de colonnes + segment de lignes
                            foreach ($champ->getEnfants() as $idx => $enfant) {
                                $this->addChamp(
                                    $odf,
                                    ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '', sprintf('valeur%d', $idx)]),
                                    $enfant->getNom()
                                );
                            }

                            $segName = ChampNamer::getLoopName($nomInterne.' '.($rubrique->getNom() ?? '').' '.($champ->getNom() ?? '').' valeurs');
                            try {
                                $segmentTableau = $odf->setSegment($segName);
                                /** @var array<int, array<int, Valeur|null>> $lignes */
                                $lignes = $champData['lignes'] ?? [];
                                if (0 === count($lignes)) {
                                    foreach ($champ->getEnfants() as $idx => $enfant) {
                                        $this->addChamp($segmentTableau, sprintf('valeur%d', $idx), '');
                                    }

                                    $segmentTableau->merge();
                                } else {
                                    foreach ($lignes as $ligne) {
                                        foreach ($champ->getEnfants() as $idx => $enfant) {
                                            $enfantId = $enfant->getId();
                                            /** @var Valeur|null $valeur */
                                            $valeur = null !== $enfantId ? ($ligne[$enfantId] ?? null) : null;
                                            $val = $this->resolveValeurForFormulaire($enfant->getType(), $valeur);
                                            $this->addChamp($segmentTableau, sprintf('valeur%d', $idx), $val);
                                        }

                                        $segmentTableau->merge();
                                    }
                                }

                                $odf->mergeSegment($segmentTableau);
                            } catch (\Exception $e) {
                            }
                        } else {
                            // Branche non-tableau : sous-champs directs
                            /** @var array<mixed> $sousChamps */
                            $sousChamps = $champData['sous_champs'] ?? [];
                            foreach ($sousChamps as $souschampData) {
                                /** @var array{champ: Champ, valeur: Valeur|null} $souschampData */
                                /** @var Champ $souschamp */
                                $souschamp = $souschampData['champ'];
                                /** @var Valeur|null $valeur */
                                $valeur = $souschampData['valeur'];
                                $val = $this->resolveValeurForFormulaire($souschamp->getType(), $valeur);
                                $this->addChamp(
                                    $odf,
                                    ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '', $souschamp->getNom() ?? '']),
                                    $val
                                );
                            }
                        }
                    } else {
                        // Branche feuille
                        /** @var Valeur|null $valeur */
                        $valeur = $champData['valeur'] ?? null;
                        $val = $this->resolveValeurForFormulaire($champ->getType(), $valeur);
                        $this->addChamp(
                            $odf,
                            ChampNamer::getFullFusionName($nomInterne, [$rubrique->getNom() ?? '', $champ->getNom() ?? '']),
                            $val
                        );
                    }
                }
            }
        }
    }

    /**
     * Résout la valeur d'un champ formulaire en string selon son type.
     *
     * @param \App\Entity\TypeChamp|null $typeChamp Type du champ (entité Doctrine)
     * @param Valeur|null                $valeur    Valeur Doctrine
     */
    private function resolveValeurForFormulaire(?\App\Entity\TypeChamp $typeChamp, ?Valeur $valeur): string
    {
        if (null === $valeur) {
            return '';
        }

        $typeId = null !== $typeChamp ? $typeChamp->getId() : null;

        if (TypeChamp::CHECKBOX === $typeId) {
            $checkbox = $valeur->getCheckbox();

            return (true === $checkbox) ? 'Oui' : 'Non';
        }

        if (TypeChamp::DATE === $typeId) {
            $date = $valeur->getDate();
            if (null === $date) {
                return '';
            }

            return $this->formatDateLongFr($date);
        }

        return $valeur->getStr() ?? $valeur->getLongStr() ?? '';
    }

    /**
     * Formate une date (string SQL ou null) en format court français : "d MMM yyyy" (ex: "15 janv. 2024").
     *
     * @param string|null $dateStr Date au format Y-m-d ou Y-m-d H:i:s
     */
    private function formatDateShortFr(?string $dateStr): string
    {
        if (null === $dateStr || '' === $dateStr) {
            return '';
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
        if (!$dt instanceof \DateTime) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        }

        if (!$dt instanceof \DateTime) {
            return '';
        }

        $moisCourts = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        return $dt->format('j').' '.$moisCourts[(int) $dt->format('n') - 1].' '.$dt->format('Y');
    }

    /**
     * Formate un objet DateTime en format long français : "d MMMM yyyy" (ex: "15 janvier 2024").
     */
    private function formatDateLongFr(\DateTimeInterface $dt): string
    {
        $moisLongs = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return $dt->format('j').' '.$moisLongs[(int) $dt->format('n') - 1].' '.$dt->format('Y');
    }

    /**
     * Formate une chaîne de date SQL en format long français.
     *
     * @param string|null $dateStr Date au format Y-m-d ou Y-m-d H:i:s
     */
    private function formatDateLongFrFromStr(?string $dateStr): string
    {
        if (null === $dateStr || '' === $dateStr) {
            return '';
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
        if (!$dt instanceof \DateTime) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        }

        if (!$dt instanceof \DateTime) {
            return '';
        }

        return $this->formatDateLongFr($dt);
    }
}
