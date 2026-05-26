<?php

namespace App\Service;

use App\Constant\CategorieEtablissement;
use App\Constant\GenreEtablissement;
use App\Constant\TypeChangement;
use App\Dto\AvisDerogationEtablissement;
use App\Entity\Classement;
use App\Entity\Commission;
use App\Entity\Etablissement as EtablissementEntity;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\EtablissementStatut;
use App\Entity\EtablissementType;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\IghClasse;
use App\Entity\Utilisateur;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementInformationsRepository;
use App\Repository\EtablissementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Etablissement
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var AvisDerogationsRepository */
    private $avisDerogationsRepository;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TexteApplicable */
    private $texteApplicableService;

    /** @var EtablissementPersistenceService */
    private $persistenceService;

    /** @var EtablissementInformationsRepository */
    private $informationsRepository;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        AvisDerogationsRepository $avisDerogationsRepository,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        TexteApplicable $texteApplicableService,
        EtablissementPersistenceService $persistenceService,
        EtablissementInformationsRepository $informationsRepository
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->avisDerogationsRepository = $avisDerogationsRepository;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->texteApplicableService = $texteApplicableService;
        $this->persistenceService = $persistenceService;
        $this->informationsRepository = $informationsRepository;
    }

    /**
     * Récupère les dates de la dernière visite et de la prochaine visite pour un établissement donné.
     *
     * @param EtablissementEntity $etablissement L'entité représentant l'établissement
     *
     * @return array{lastVisite: \DateTimeInterface|null, nextVisite: \DateTimeInterface|null}
     */
    public function getDatesVisites(EtablissementEntity $etablissement): array
    {
        $useDateCommissionForPeriodicity = $this->parameterBag->get('app.relance_periodicite_commission');
        $lastVisiteDossier = $this->dossierRepository->getLastVisitePeriodique($etablissement->getId(), $useDateCommissionForPeriodicity);

        $dateLastVisite = $lastVisiteDossier['date_relance'] ?? null;
        $dateNextVisite = null;

        // Si aucune date de dernière visite n'est trouvée, retourner null pour les deux dates
        if (null === $dateLastVisite) {
            return [
                'lastVisite' => null,
                'nextVisite' => null,
            ];
        }

        if (!$dateLastVisite instanceof \DateTimeInterface) {
            if (!\is_string($dateLastVisite)) {
                throw new \InvalidArgumentException('La date de dernière visite doit être une chaîne de caractères.');
            }

            $dateLastVisite = new \DateTime($dateLastVisite);
        }

        $informations = $etablissement->getLatestInformations();
        $periodicite = $informations->getPeriodicite();

        if ($periodicite && $periodicite > 0) {
            /** @var \DateTime $dateNextVisite */
            $dateNextVisite = clone $dateLastVisite;
            $dateNextVisite->modify(sprintf('+%d months', $periodicite));
        }

        return [
            'lastVisite' => $dateLastVisite,
            'nextVisite' => $dateNextVisite,
        ];
    }

    /**
     * Récupère les textes applicables d'un établissement.
     *
     * @return array{}|array{int, array{'titre': ?string, 'textes': array{int, ?string}}}
     */
    public function getTextesApplicables(int $id): array
    {
        $textesApplicablesData = $this->etablissementRepository->getTextesApplicablesByType($id);

        if (null === $textesApplicablesData) {
            return [];
        }

        $textesApplicablesValues = $textesApplicablesData->getTextesApplicables()->getValues();

        return $this->texteApplicableService->trierParType($textesApplicablesValues);
    }

    /**
     * @return AvisDerogationEtablissement[]
     */
    public function getAvisDerogations(EtablissementEntity $etablissement): array
    {
        // Batch : parent + tous les enfants en une seule requête (évite le N+1).
        $ids = [$etablissement->getId()];
        foreach ($etablissement->getFils() as $fils) {
            $ids[] = (int) $fils->getId();
        }

        $listeAvisDerogationsEtablissement = $this->avisDerogationsRepository->findForEtablissements($ids);

        $avisDerogations = [];
        foreach ($listeAvisDerogationsEtablissement as $avisDerogation) {
            $leve = false;
            $debut = null;
            $typeDossier = null;
            $dossierAvisDerogation = $avisDerogation->getDossier();

            if (null === $dossierAvisDerogation) {
                $this->logger->warning("Aucun dossier associé à l'avis/dérogation.", [
                    'avis_derogation_id' => $avisDerogation->getId(),
                ]);

                continue;
            }

            if (null !== $avisDerogation->getDossierLie()) {
                if (null !== $avisDerogation->getDossierLie()->getType() && null !== $avisDerogation->getDossierLie()->getType()->getId()) {
                    $typeDossier = $avisDerogation->getDossierLie()->getType()->getId();
                }

                $leve = true;
            }

            if (null !== $dossierAvisDerogation->getDateCommission()) {
                $debut = $dossierAvisDerogation->getDateCommission();
            } elseif (null !== $dossierAvisDerogation->getDateVisite()) {
                $debut = $dossierAvisDerogation->getDateVisite()->format('Y-m-d');
            }

            if (
                in_array(null, [$dossierAvisDerogation->getId(), $avisDerogation->getId(), $avisDerogation->getTitre(), $avisDerogation->getType()], true)
            ) {
                $this->logger->warning("Une des données requises de l'avis/dérogation n'est pas renseignée", [
                    'dossier_id' => $dossierAvisDerogation->getId(),
                    'avis_derogation_id' => $avisDerogation->getId(),
                    'avis_derogation_titre' => $avisDerogation->getTitre(),
                    'avis_derogation_type' => $avisDerogation->getType(),
                ]);

                continue;
            }

            $avisDerogations[] = new AvisDerogationEtablissement(
                $dossierAvisDerogation->getId(),
                $avisDerogation->getId(),
                $avisDerogation->getTitre(),
                $avisDerogation->getType(),
                $leve,
                $debut,
                $typeDossier
            );
        }

        return $avisDerogations;
    }

    /**
     * Calcule et retourne la périodicité affichée sous forme textuelle pour un établissement donné.
     *
     * @param EtablissementEntity $etablissement L'entité de l'établissement concerné
     *
     * @return string|null la chaîne représentant la périodicité au format "X mois", "X ans", ou "X ans et Y mois"
     */
    public function getDisplayedPeriodicity(EtablissementEntity $etablissement): ?string
    {
        $numberOfMonthsInAYear = 12;

        $information = $etablissement->getLatestInformations();

        $periodicity = $information->getPeriodicite();
        $genre = $information->getGenre();

        if (!$genre instanceof Genre) {
            throw new \InvalidArgumentException(\sprintf("Le genre de l'établissement %d n'est pas renseigné.", $etablissement->getId()));
        }

        $genreId = $genre->getId();

        if (1 === $genreId) {
            foreach ($etablissement->getFils() as $enfant) {
                $informationsEnfant = $enfant->getLatestInformations();
                $informationsEnfantGenre = $informationsEnfant->getGenre();
                $informationsEnfantStatut = $informationsEnfant->getStatut();

                if (!$informationsEnfantGenre instanceof Genre) {
                    throw new \InvalidArgumentException(\sprintf("Le genre de l'établissement %d n'est pas renseigné.", $enfant->getId()));
                }

                if (!$informationsEnfantStatut instanceof EtablissementStatut) {
                    throw new \InvalidArgumentException(\sprintf("Le statut de l'établissement %d n'est pas renseigné.", $enfant->getId()));
                }

                if (2 !== $informationsEnfantGenre->getId()) {
                    continue;
                }

                if (2 !== $informationsEnfantStatut->getId()) {
                    continue;
                }

                $periodiciteEnfant = $informationsEnfant->getPeriodicite();
                if (null === $periodiciteEnfant) {
                    continue;
                }

                if (0 === $periodiciteEnfant) {
                    continue;
                }

                if (null === $periodicity || $periodicity > $periodiciteEnfant) {
                    $periodicity = $periodiciteEnfant;
                }
            }
        } elseif (3 === $genreId && ($parent = $etablissement->getParent()) !== null) {
            $periodicity = $parent->getLatestInformations()->getPeriodicite();
        }

        if (null === $periodicity) {
            return null;
        }

        $periodicityUnit = 'mois';
        $periodicityString = sprintf('%s %s', $periodicity, $periodicityUnit);

        if (!$this->parameterBag->get('app.periodicite_annees')) {
            return $periodicityString;
        }

        if ($periodicity < $numberOfMonthsInAYear) {
            return $periodicityString;
        }

        $periodicityYear = intdiv($periodicity, $numberOfMonthsInAYear);
        $periodicityUnit = 1 === $periodicityYear ? 'an' : 'ans';

        $periodicityString = sprintf('%d %s', $periodicityYear, $periodicityUnit);

        $remainder = $periodicity % $numberOfMonthsInAYear;
        $remainderUnit = 'mois';

        if (0 !== $remainder) {
            return sprintf('%s et %s %s', $periodicityString, $remainder, $remainderUnit);
        }

        return $periodicityString;
    }

    /**
     * Calcule les données pratiques selon le genre de l'établissement.
     *
     * @return array{nbPreventionnistes: ?int, dureeVisite: ?\DateTime}
     */
    public function calculateDonneesPratiques(EtablissementEntity $etablissement): array
    {
        $informations = $etablissement->getLatestInformations();
        $genre = $informations->getGenre();
        $genreId = $genre instanceof Genre ? $genre->getId() : null;

        // Site (genre 1)
        if (1 === $genreId) {
            $dureeVisiteTotal = (new \DateTime())->setTime(0, 0, 0);

            foreach ($etablissement->getFils() as $enfant) {
                $dureeVisite = $enfant->getDureeVisite();
                $nbPrev = $enfant->getNbPrev();

                if ($dureeVisite instanceof \DateTimeInterface && $nbPrev) {
                    $hours = (int) $dureeVisite->format('H');
                    $minutes = (int) $dureeVisite->format('i');
                    $seconds = (int) $dureeVisite->format('s');

                    $intervalDureeVisite = new \DateInterval(
                        sprintf('PT%dH%dM%dS', $hours * $nbPrev, $minutes * $nbPrev, $seconds * $nbPrev)
                    );

                    $dureeVisiteTotal->add($intervalDureeVisite);
                }
            }

            return [
                'nbPreventionnistes' => null,
                'dureeVisite' => $dureeVisiteTotal,
            ];
        }

        // Cellule (genre 3) ou autres genres
        return [
            'nbPreventionnistes' => $etablissement->getNbPrev(),
            'dureeVisite' => $etablissement->getDureeVisite(),
        ];
    }

    /**
     * Vérifie la présence d'un DUS (ID_FONCTION = 8) dans les contacts.
     */
    public function hasContactDus(EtablissementEntity $etablissement): bool
    {
        $allContacts = $etablissement->getContacts()->toArray();

        // Récupération des contacts des parents
        $currentEtablissement = $etablissement;
        while (null !== $currentEtablissement->getParent()) {
            $parent = $currentEtablissement->getParent();
            $allContacts = array_merge($allContacts, $parent->getContacts()->toArray());
            $currentEtablissement = $parent;
        }

        foreach ($allContacts as $contact) {
            if ($contact->getFonction() && 8 === $contact->getFonction()->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rétablit un établissement supprimé en remettant à null les champs de suppression.
     */
    public function restore(EtablissementEntity $etablissement): void
    {
        $etablissement->setDateSuppression(null);
        $etablissement->setDeletedBy(null);

        $this->entityManager->flush();
    }

    /**
     * Retourne les données du descriptif original (champs fixes) d'un établissement,
     * groupées par section pour l'affichage.
     *
     * @return array{
     *     descriptif: ?string,
     *     historique: ?string,
     *     derogation: ?string,
     *     techniquesGroupes: array<string, array<string, array{value: mixed, type: string}>>
     * }
     */
    public function getDescriptifOriginal(EtablissementEntity $etablissement): array
    {
        $techniquesGroupes = [
            'Implantation' => [
                'Surface emprise au sol (m²)' => ['value' => $etablissement->getDesctechImplantationSurface(), 'type' => 'int'],
                'Surface totale (m²)' => ['value' => $etablissement->getDesctechImplantationSurfaceTotale(), 'type' => 'int'],
                'Surface accessible au public (m²)' => ['value' => $etablissement->getDesctechImplantationSurfaceAccPublic(), 'type' => 'int'],
                'Surface de plancher (m²)' => ['value' => $etablissement->getDesctechImplantationShon(), 'type' => 'int'],
                'Nombre de niveaux' => ['value' => $etablissement->getDesctechImplantationNbNiveaux(), 'type' => 'int'],
                'Plancher bas du dernier niveau (m)' => ['value' => $etablissement->getDesctechImplantationPbdn(), 'type' => 'int'],
            ],
            'Desserte' => [
                'Nombre de façades accessibles' => ['value' => $etablissement->getDesctechDesserteNbFacadeLibre(), 'type' => 'int'],
                'Voie engin' => ['value' => $etablissement->getDesctechDesserteVoieEngin(), 'type' => 'int'],
                'Voie échelle' => ['value' => $etablissement->getDesctechDesserteVoieEchelle(), 'type' => 'int'],
                'Espace libre' => ['value' => $etablissement->getDesctechDesserteEspaceLibre(), 'type' => 'int'],
            ],
            'Isolement par rapport aux tiers' => [
                'Latéral CF (minute)' => ['value' => $etablissement->getDesctechIsolementLateralCf(), 'type' => 'int'],
                'Superposé CF (minute)' => ['value' => $etablissement->getDesctechIsolementSuperposeCf(), 'type' => 'int'],
                'Vis-à-vis (m)' => ['value' => $etablissement->getDesctechIsolementVisAVis(), 'type' => 'int'],
            ],
            'Stabilité au feu' => [
                'Structure SF (minute)' => ['value' => $etablissement->getDesctechStabiliteStructureSf(), 'type' => 'int'],
                'Plancher SF (minute)' => ['value' => $etablissement->getDesctechStabilitePlancher(), 'type' => 'int'],
            ],
            'Distribution intérieure' => [
                'Cloisonnement traditionnel' => ['value' => $etablissement->getDesctechDistributionCloisonnementTrad(), 'type' => 'tinyint'],
                'Secteurs' => ['value' => $etablissement->getDesctechDistributionSecteurs(), 'type' => 'tinyint'],
                'Compartiments' => ['value' => $etablissement->getDesctechDistributionCompartiments(), 'type' => 'tinyint'],
            ],
            'Locaux à risques' => [
                'Nombre de locaux à risques moyens' => ['value' => $etablissement->getDesctechLocauxRisqueNbMoyens(), 'type' => 'int'],
                'Nombre de locaux à risques importants' => ['value' => $etablissement->getDesctechLocauxRisqueNbImportants(), 'type' => 'int'],
            ],
            "Espaces d'attentes sécurisés" => [
                'Nombre' => ['value' => $etablissement->getDesctechEspacesNombre(), 'type' => 'int'],
                'Nombre de niveaux concernés' => ['value' => $etablissement->getDesctechEspacesNiveauConcerne(), 'type' => 'int'],
            ],
            'Désenfumage' => [
                'Naturel' => ['value' => $etablissement->getDesctechDesenfumageNaturel(), 'type' => 'tinyint'],
                'Mécanique' => ['value' => $etablissement->getDesctechDesenfumageMecanique(), 'type' => 'tinyint'],
                'Commentaire' => ['value' => $etablissement->getDesctechDesenfumageCommentaire(), 'type' => 'text'],
            ],
            'Chaufferies' => [
                'Nombre' => ['value' => $etablissement->getDesctechChaufferiesNb(), 'type' => 'int'],
                'Puissance max (kw)' => ['value' => $etablissement->getDesctechChaufferiesPuissMax(), 'type' => 'int'],
                'Puissance totale (kw)' => ['value' => $etablissement->getDesctechChaufferiesPuissanceTotale(), 'type' => 'int'],
                'Nombre de chaufferies 30kw' => ['value' => $etablissement->getDesctechChaufferiesNb30Kw(), 'type' => 'int'],
                'Nombre de chaufferies 70kw' => ['value' => $etablissement->getDesctechChaufferiesNb70Kw(), 'type' => 'int'],
                'Nombre de chaufferies 2mw' => ['value' => $etablissement->getDesctechChaufferiesNb2Mw(), 'type' => 'int'],
            ],
            "Localisation des coupures d'énergies" => [
                'Gaz' => ['value' => $etablissement->getDesctechCoupureNrjGaz(), 'type' => 'text'],
                'Électricité' => ['value' => $etablissement->getDesctechCoupureNrjElec(), 'type' => 'text'],
                'Photovoltaïque' => ['value' => $etablissement->getDesctechCoupureNrjPhotovoltaique(), 'type' => 'text'],
                'Autre' => ['value' => $etablissement->getDesctechCoupureNrjAutre(), 'type' => 'text'],
            ],
            'Ascenseurs' => [
                'Nombre ascenseurs total' => ['value' => $etablissement->getDesctechAscenseursNbTotal(), 'type' => 'int'],
                'Nombre ascenseurs de type AS4' => ['value' => $etablissement->getDesctechAscenseursNbAs4(), 'type' => 'int'],
            ],
            'Moyens de secours' => [
                'Colonnes sèches' => ['value' => $etablissement->getDesctechMoyensSecoursColonnesSeches(), 'type' => 'tinyint'],
                'Colonnes humides' => ['value' => $etablissement->getDesctechMoyensSecoursColonnesHumides(), 'type' => 'tinyint'],
                'RIA' => ['value' => $etablissement->getDesctechMoyensSecoursRia(), 'type' => 'tinyint'],
                'Sprinkleur' => ['value' => $etablissement->getDesctechMoyensSecoursSprinkleur(), 'type' => 'tinyint'],
                "Brouillard d'eau" => ['value' => $etablissement->getDesctechMoyensSecoursBrouillardEau(), 'type' => 'tinyint'],
            ],
            'PC Sécurité' => [
                'Présence' => ['value' => $etablissement->getDesctechPcSecuPresence(), 'type' => 'tinyint'],
                'Localisation' => ['value' => $etablissement->getDesctechPcSecuLocalisation(), 'type' => 'text'],
            ],
            'SSI' => [
                'Présence' => ['value' => $etablissement->getDesctechSsiPresence(), 'type' => 'tinyint'],
                'Catégorie SSI' => ['value' => $etablissement->getDesctechSsiCategorie(), 'type' => 'char'],
                'Alarme type' => ['value' => $etablissement->getDesctechSsiAlarmeType(), 'type' => 'char'],
            ],
            'Service de sécurité' => [
                'Personne qualifiée désignée (EL18)' => ['value' => $etablissement->getDesctechServiceSecuEl18(), 'type' => 'tinyint'],
                'Personnels désignés' => ['value' => $etablissement->getDesctechServiceSecuPersonnelsDesignes(), 'type' => 'tinyint'],
                'Agent de sécurité' => ['value' => $etablissement->getDesctechServiceSecuAgentSecu(), 'type' => 'int'],
                "Chef d'équipe sécurité" => ['value' => $etablissement->getDesctechServiceSecuChefEquipe(), 'type' => 'int'],
                'Chef de service sécurité' => ['value' => $etablissement->getDesctechServiceSecuChefDeService(), 'type' => 'int'],
                'Sapeurs Pompiers' => ['value' => $etablissement->getDesctechServiceSecuSp(), 'type' => 'int'],
                'Commentaire sur les SP' => ['value' => $etablissement->getDesctechServiceSecuCommentaireSp(), 'type' => 'text'],
            ],
            'Défense incendie' => [
                "Nombre de point d'eau" => ['value' => $etablissement->getDesctechDefensePtEau(), 'type' => 'int'],
                "Volume des points d'eau (m3)" => ['value' => $etablissement->getDesctechDefenseVolumePtEau(), 'type' => 'int'],
                "Commentaires sur le / les points d'eau naturel" => ['value' => $etablissement->getDesctechDefensePtEauCommentaire(), 'type' => 'text'],
                'PI' => ['value' => $etablissement->getDesctechDefensePi(), 'type' => 'int'],
                'BI' => ['value' => $etablissement->getDesctechDefenseBi(), 'type' => 'int'],
                'Débit simultané (m3/h)' => ['value' => $etablissement->getDesctechDefenseDebitSimultane(), 'type' => 'int'],
            ],
            'Risques' => [
                'Risques naturels' => ['value' => $etablissement->getRisquesNaturels(), 'type' => 'text'],
                'Risques technologiques' => ['value' => $etablissement->getRisquesTechnologiques(), 'type' => 'text'],
            ],
        ];

        return [
            'descriptif' => $etablissement->getDescriptif(),
            'historique' => $etablissement->getHistoriqueDescriptif(),
            'derogation' => $etablissement->getDescriptifDerogation(),
            'techniquesGroupes' => $techniquesGroupes,
        ];
    }

    /**
     * Sauvegarde le descriptif original d'un établissement.
     */
    public function saveDescriptifOriginal(): void
    {
        $this->entityManager->flush();
    }

    /**
     * Convertit de manière sécurisée une valeur de type mixed en entier.
     *
     * @param mixed $val
     */
    private static function toInt($val, int $default = 0): int
    {
        return is_scalar($val) ? (int) $val : $default;
    }

    /**
     * Convertit de manière sécurisée une valeur de type mixed en chaîne.
     *
     * @param mixed $val
     */
    private static function toString($val, string $default = ''): string
    {
        return is_scalar($val) ? (string) $val : $default;
    }

    /**
     * Crée un établissement avec sa fiche d'informations initiale dans une transaction Doctrine.
     *
     * Port de Service_Etablissement::save() (legacy Zend) pour la création uniquement.
     * Gère les 10 genres : site, ERP, cellule, habitation, IGH, BUP, camping,
     * manifestation temporaire, IOP et zone.
     *
     * @param int                  $idGenre Identifiant du genre de l'établissement
     * @param array<string, mixed> $data    Données du formulaire (clés correspondant aux colonnes legacy)
     * @param Utilisateur|null     $user    Utilisateur courant (préventionniste par défaut)
     *
     * @return int L'identifiant de l'établissement créé
     *
     * @throws \Exception En cas d'erreur (la transaction est annulée)
     */
    public function save(int $idGenre, array $data, ?Utilisateur $user = null): int
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            // --- Établissement (entité principale, champs non historisés) ---
            $etablissement = new EtablissementEntity();
            $etablissement->setTelephone(
                isset($data['TELEPHONE_ETABLISSEMENT']) ? self::toString($data['TELEPHONE_ETABLISSEMENT']) : null
            );
            $etablissement->setFax(
                isset($data['FAX_ETABLISSEMENT']) ? self::toString($data['FAX_ETABLISSEMENT']) : null
            );
            $etablissement->setCourriel(
                isset($data['COURRIEL_ETABLISSEMENT']) ? self::toString($data['COURRIEL_ETABLISSEMENT']) : null
            );
            $etablissement->setNbPrev(null);
            $etablissement->setDureeVisite(null);

            $em->persist($etablissement);
            $em->flush(); // nécessaire pour obtenir l'ID avant de définir NUMEROID

            // NUMEROID = fourni ou ID par défaut
            $rawNumeroid = self::toString($data['NUMEROID_ETABLISSEMENT'] ?? '');
            $numeroid = '' !== $rawNumeroid ? $rawNumeroid : self::toString($etablissement->getId());
            $etablissement->setNumero($numeroid);

            // --- Fiche d'informations (historisée) ---
            $informations = new EtablissementInformations();
            $informations->setDate(new \DateTime('today'));
            $informations->setLibelle(self::toString($data['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? ''));
            $informations->setEtablissement($etablissement);

            /** @var Genre $genre */
            $genre = $em->getReference(Genre::class, $idGenre);
            $informations->setGenre($genre);

            $rawStatutId = self::toString($data['ID_STATUT'] ?? '');
            $statutId = '' !== $rawStatutId ? self::toInt($rawStatutId) : 1;
            /** @var EtablissementStatut $statut */
            $statut = $em->getReference(EtablissementStatut::class, $statutId);
            $informations->setStatut($statut);

            if (null !== $user) {
                $informations->setUtilisateur($user);
            }

            // Remise à zéro des champs genre-spécifiques (null = non applicable)
            $informations->setIcpe(null);
            $informations->setR14320(null);
            $informations->setDroitPublic(null);
            $informations->setLocalSommeil(null);
            $informations->setEffectifPublic(null);
            $informations->setEffectifPersonnel(null);
            $informations->setEffectifHeberge(null);
            $informations->setEffectifJustifiantClassement(null);
            $informations->setEffectifHabitation(null);
            $informations->setEffectifCaravane(null);
            $informations->setEffectifEmplacementNu(null);
            $informations->setEffectifDivers(null);

            // --- Hydratation genre-spécifique ---
            $this->hydrateInformations($informations, $etablissement, $idGenre, $data, $em);

            $em->persist($informations);

            // --- Préventionnistes ---
            $this->persistenceService->persistPreventionnistes($informations, $data, $em);

            // --- Rubriques ICPE (BUP uniquement) ---
            $this->persistenceService->persistRubriques($idGenre, $informations, $data, $em);

            // --- Plans (ERP, Cellule, IGH, BUP, Camping, Manifestation temporaire, IOP) ---
            $this->persistenceService->persistPlans($idGenre, $informations, $data, $em);

            // --- Activités secondaires (ERP et Cellule) ---
            $this->persistenceService->persistActivitesSecondaires($idGenre, $informations, $data, $em);

            // --- Adresses (ERP, Habitation, IGH, BUP, Camping, Manifestation, IOP, Zone) ---
            $this->persistenceService->persistAdresses($idGenre, $etablissement, $data, $em);

            // --- Établissements fils ---
            $this->persistenceService->persistEtablissementsLies($idGenre, $etablissement, $data);

            // --- Père de l'établissement ---
            $this->persistenceService->persistPere($idGenre, $etablissement, $data);

            $em->flush();
            $connection->commit();

            $this->logger->info('Établissement créé', ['id' => $etablissement->getId(), 'genre' => $idGenre]);

            return $etablissement->getId();
        } catch (\Exception $exception) {
            $connection->rollBack();
            $this->logger->error('Erreur création établissement', ['error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    /**
     * Met à jour un établissement existant avec sa fiche d'informations.
     *
     * Port de Service_Etablissement::save() (legacy Zend) pour la mise à jour.
     * Conserve le comportement d'historisation : si une fiche d'informations existe à la date
     * du jour, elle est mise à jour ; sinon, une nouvelle fiche est créée.
     *
     * @param int                  $idGenre         Identifiant du genre de l'établissement
     * @param array<string, mixed> $data            Données du formulaire (clés correspondant aux colonnes legacy)
     * @param int                  $idEtablissement Identifiant de l'établissement à mettre à jour
     * @param Utilisateur|null     $user            Utilisateur courant
     *
     * @throws \InvalidArgumentException Si l'établissement n'existe pas
     * @throws \Exception                En cas d'erreur (la transaction est annulée)
     */
    public function update(int $idGenre, array $data, int $idEtablissement, ?Utilisateur $user = null): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $etablissement = $this->etablissementRepository->find($idEtablissement);
            if (null === $etablissement) {
                throw new \InvalidArgumentException(sprintf('Établissement %d introuvable.', $idEtablissement));
            }

            // --- Champs non historisés ---
            $etablissement->setTelephone(
                isset($data['TELEPHONE_ETABLISSEMENT']) ? self::toString($data['TELEPHONE_ETABLISSEMENT']) : null
            );
            $etablissement->setFax(
                isset($data['FAX_ETABLISSEMENT']) ? self::toString($data['FAX_ETABLISSEMENT']) : null
            );
            $etablissement->setCourriel(
                isset($data['COURRIEL_ETABLISSEMENT']) ? self::toString($data['COURRIEL_ETABLISSEMENT']) : null
            );
            $etablissement->setNbPrev(null);
            $etablissement->setDureeVisite(null);

            $rawNumeroid = self::toString($data['NUMEROID_ETABLISSEMENT'] ?? '');
            $numeroid = '' !== $rawNumeroid ? $rawNumeroid : self::toString($etablissement->getId());
            $etablissement->setNumero($numeroid);

            // --- Fiche d'informations (historisée) : upsert à la date du jour ---
            $today = new \DateTime('today');
            $existingInfo = $this->informationsRepository->findOneBy([
                'etablissement' => $idEtablissement,
                'date' => $today,
            ]);

            if (null !== $existingInfo) {
                $informations = $existingInfo;
                // Suppression des relations existantes de la fiche
                foreach ($informations->getPlans()->toArray() as $plan) {
                    $informations->removePlan($plan);
                }

                foreach ($informations->getRubriquesIcpe()->toArray() as $rubrique) {
                    $informations->removeRubriqueIcpe($rubrique);
                }

                foreach ($informations->getTypesActivitesSecondaires()->toArray() as $ta) {
                    $informations->removeTypesActivitesSecondaire($ta);
                }

                $informations->clearPreventionnistes();
            } else {
                $informations = new EtablissementInformations();
                $informations->setDate($today);
                $informations->setEtablissement($etablissement);
            }

            // Suppression des adresses et liens existants (recréés ensuite)
            foreach ($etablissement->getAdresses()->toArray() as $adresse) {
                $etablissement->removeAdress($adresse);
            }

            foreach ($etablissement->getFils()->toArray() as $fils) {
                $etablissement->removeFils($fils);
            }

            foreach ($etablissement->getParents()->toArray() as $parent) {
                $etablissement->removeParent($parent);
            }

            // --- Champs communs de la fiche ---
            $informations->setLibelle(self::toString($data['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? ''));

            /** @var Genre $genre */
            $genre = $em->getReference(Genre::class, $idGenre);
            $informations->setGenre($genre);

            $rawStatutId = self::toString($data['ID_STATUT'] ?? '');
            $statutId = '' !== $rawStatutId ? self::toInt($rawStatutId) : 1;
            /** @var EtablissementStatut $statut */
            $statut = $em->getReference(EtablissementStatut::class, $statutId);
            $informations->setStatut($statut);

            if (null !== $user) {
                $informations->setUtilisateur($user);
            }

            // Remise à zéro des champs genre-spécifiques
            $informations->setIcpe(null);
            $informations->setR14320(null);
            $informations->setDroitPublic(null);
            $informations->setLocalSommeil(null);
            $informations->setEffectifPublic(null);
            $informations->setEffectifPersonnel(null);
            $informations->setEffectifHeberge(null);
            $informations->setEffectifJustifiantClassement(null);
            $informations->setEffectifHabitation(null);
            $informations->setEffectifCaravane(null);
            $informations->setEffectifEmplacementNu(null);
            $informations->setEffectifDivers(null);

            // Remise à zéro des associations genre-spécifiques avant le switch :
            // nécessaire lorsque $existingInfo existe déjà pour aujourd'hui,
            // pour éviter de conserver une ancienne valeur si l'utilisateur choisit
            // l'option vide ("— Choisir —") ou change de genre.
            $informations->setCategorie(null);
            $informations->setType(null);
            $informations->setTypeActivite(null);
            $informations->setCommission(null);
            $informations->setFamille(null);
            $informations->setClasse(null);
            $informations->setClassement(null);
            $informations->setPeriodicite(null);

            // --- Hydratation genre-spécifique ---
            $this->hydrateInformations($informations, $etablissement, $idGenre, $data, $em);

            $em->persist($informations);

            // --- Persistance des relations via le service commun ---
            $this->persistenceService->persistPreventionnistes($informations, $data, $em);
            $this->persistenceService->persistRubriques($idGenre, $informations, $data, $em);
            $this->persistenceService->persistPlans($idGenre, $informations, $data, $em);
            $this->persistenceService->persistActivitesSecondaires($idGenre, $informations, $data, $em);
            $this->persistenceService->persistAdresses($idGenre, $etablissement, $data, $em);
            $this->persistenceService->persistEtablissementsLies($idGenre, $etablissement, $data);
            $this->persistenceService->persistPere($idGenre, $etablissement, $data);

            $em->flush();
            $connection->commit();

            $this->logger->info('Établissement mis à jour', ['id' => $idEtablissement, 'genre' => $idGenre]);
        } catch (\Exception $exception) {
            $connection->rollBack();
            $this->logger->error('Erreur mise à jour établissement', ['error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    /**
     * Hydrate les champs genre-spécifiques d'une fiche d'informations.
     *
     * Logique commune à la création (save) et la mise à jour (update) d'un établissement.
     *
     * @param EtablissementInformations $informations  La fiche d'informations à hydrater
     * @param EtablissementEntity       $etablissement L'entité établissement (pour nbPrev / dureeVisite)
     * @param int                       $idGenre       Identifiant du genre
     * @param array<string, mixed>      $data          Données du formulaire
     * @param EntityManagerInterface    $em            EntityManager (pour getReference)
     */
    private function hydrateInformations(
        EtablissementInformations $informations,
        EtablissementEntity $etablissement,
        int $idGenre,
        array $data,
        EntityManagerInterface $em
    ): void {
        switch ($idGenre) {
            case GenreEtablissement::ERP:
                $rawCategorieId = self::toString($data['ID_CATEGORIE'] ?? '');
                if ('' !== $rawCategorieId) {
                    /** @var EtablissementCategorie $cat */
                    $cat = $em->getReference(EtablissementCategorie::class, self::toInt($rawCategorieId));
                    $informations->setCategorie($cat);
                }

                $informations->setPeriodicite(self::toInt($data['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? 0));

                $rawTypeId = self::toString($data['ID_TYPE'] ?? '');
                if ('' !== $rawTypeId) {
                    /** @var EtablissementType $typeEnt */
                    $typeEnt = $em->getReference(EtablissementType::class, self::toInt($rawTypeId));
                    $informations->setType($typeEnt);
                }

                $rawActiviteId = self::toString($data['ID_TYPEACTIVITE'] ?? '');
                if ('' !== $rawActiviteId) {
                    /** @var EtablissementActivite $activite */
                    $activite = $em->getReference(EtablissementActivite::class, self::toInt($rawActiviteId));
                    $informations->setTypeActivite($activite);
                }

                $informations->setR14320(!empty($data['R14320_ETABLISSEMENTINFORMATIONS']));
                $informations->setDroitPublic(!empty($data['DROITPUBLIC_ETABLISSEMENTINFORMATIONS']));
                $localSommeil = !empty($data['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS']);
                $informations->setLocalSommeil($localSommeil);
                $informations->setEffectifPublic(self::toInt($data['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
                if ($localSommeil) {
                    $informations->setEffectifHeberge(self::toInt($data['EFFECTIFHEBERGE_ETABLISSEMENTINFORMATIONS'] ?? 0));
                }

                // Effectif justifiant classement uniquement pour 5ème catégorie
                if ('' !== $rawCategorieId && CategorieEtablissement::CINQUIEME === self::toInt($rawCategorieId)) {
                    $informations->setEffectifJustifiantClassement(
                        self::toInt($data['EFFECTIFJUSTIFIANTCLASSEMENT_ETABLISSEMENTINFORMATIONS'] ?? 0)
                    );
                }

                $rawCommissionId = self::toString($data['ID_COMMISSION'] ?? '');
                if ('' !== $rawCommissionId) {
                    /** @var Commission $commission */
                    $commission = $em->getReference(Commission::class, self::toInt($rawCommissionId));
                    $informations->setCommission($commission);
                }

                $etablissement->setNbPrev(self::toInt($data['NBPREV_ETABLISSEMENT'] ?? 0));
                if (!empty($data['DUREEVISITE_ETABLISSEMENT'])) {
                    $raw = self::toString($data['DUREEVISITE_ETABLISSEMENT']);
                    $duree = \DateTime::createFromFormat('H:i', $raw) ?: \DateTime::createFromFormat('G:i', $raw);
                    if ($duree instanceof \DateTime) {
                        $etablissement->setDureeVisite($duree);
                    }
                }

                break;

            case GenreEtablissement::CELLULE:
                $rawTypeId = self::toString($data['ID_TYPE'] ?? '');
                if ('' !== $rawTypeId) {
                    /** @var EtablissementType $typeEnt */
                    $typeEnt = $em->getReference(EtablissementType::class, self::toInt($rawTypeId));
                    $informations->setType($typeEnt);
                }

                $rawActiviteId = self::toString($data['ID_TYPEACTIVITE'] ?? '');
                if ('' !== $rawActiviteId) {
                    /** @var EtablissementActivite $activite */
                    $activite = $em->getReference(EtablissementActivite::class, self::toInt($rawActiviteId));
                    $informations->setTypeActivite($activite);
                }

                $informations->setR14320(!empty($data['R14320_ETABLISSEMENTINFORMATIONS']));
                $informations->setEffectifPublic(self::toInt($data['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $etablissement->setNbPrev(self::toInt($data['NBPREV_ETABLISSEMENT'] ?? 0));
                if (!empty($data['DUREEVISITE_ETABLISSEMENT'])) {
                    $raw = self::toString($data['DUREEVISITE_ETABLISSEMENT']);
                    $duree = \DateTime::createFromFormat('H:i', $raw) ?: \DateTime::createFromFormat('G:i', $raw);
                    if ($duree instanceof \DateTime) {
                        $etablissement->setDureeVisite($duree);
                    }
                }

                break;

            case GenreEtablissement::HABITATION:
                $rawFamilleId = self::toString($data['ID_FAMILLE'] ?? '');
                if ('' !== $rawFamilleId) {
                    /** @var Famille $famille */
                    $famille = $em->getReference(Famille::class, self::toInt($rawFamilleId));
                    $informations->setFamille($famille);
                }

                break;

            case GenreEtablissement::IGH:
                $rawClasseId = self::toString($data['ID_CLASSE'] ?? '');
                if ('' !== $rawClasseId) {
                    /** @var IghClasse $classe */
                    $classe = $em->getReference(IghClasse::class, self::toInt($rawClasseId));
                    $informations->setClasse($classe);
                }

                $informations->setPeriodicite(self::toInt($data['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? 0));

                $informations->setEffectifPublic(self::toInt($data['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $rawCommissionId = self::toString($data['ID_COMMISSION'] ?? '');
                if ('' !== $rawCommissionId) {
                    /** @var Commission $commission */
                    $commission = $em->getReference(Commission::class, self::toInt($rawCommissionId));
                    $informations->setCommission($commission);
                }

                $etablissement->setNbPrev(self::toInt($data['NBPREV_ETABLISSEMENT'] ?? 0));
                if (!empty($data['DUREEVISITE_ETABLISSEMENT'])) {
                    $raw = self::toString($data['DUREEVISITE_ETABLISSEMENT']);
                    $duree = \DateTime::createFromFormat('H:i', $raw) ?: \DateTime::createFromFormat('G:i', $raw);
                    if ($duree instanceof \DateTime) {
                        $etablissement->setDureeVisite($duree);
                    }
                }

                break;

            case GenreEtablissement::BUP:
                $informations->setIcpe(!empty($data['ICPE_ETABLISSEMENTINFORMATIONS']));
                $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));

                break;

            case GenreEtablissement::CAMPING:
                $informations->setEffectifCaravane(self::toInt($data['EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifHabitation(self::toInt($data['EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifEmplacementNu(self::toInt($data['EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifDivers(self::toInt($data['EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS'] ?? 0));

                break;

            case GenreEtablissement::MANIFESTATION_TEMPORAIRE:
            case GenreEtablissement::IOP:
                $informations->setEffectifPublic(self::toInt($data['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0));
                $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));

                break;

            case GenreEtablissement::ZONE:
                $rawClassementId = self::toString($data['ID_CLASSEMENT'] ?? '');
                if ('' !== $rawClassementId) {
                    /** @var Classement $classement */
                    $classement = $em->getReference(Classement::class, self::toInt($rawClassementId));
                    $informations->setClassement($classement);
                }

                break;

            default:
                break;
        }
    }

    /**
     * Vérifie si une modification d'établissement déclenche une alerte.
     *
     * Port de Service_Etablissement::checkAlerte() (legacy Zend).
     * Compare les données actuelles de l'établissement avec les données soumises
     * pour détecter un changement de statut ou de classement.
     *
     * @param EtablissementEntity  $etablissement L'établissement avant modification
     * @param array<string, mixed> $postData      Les données du formulaire POST
     *
     * @return int|false Le type de changement (TypeChangement::STATUT ou TypeChangement::CLASSEMENT), ou false
     */
    public function checkAlerte(EtablissementEntity $etablissement, array $postData)
    {
        $alerte = false;
        $informations = $etablissement->getLatestInformations();

        $currentStatut = $informations->getStatut();
        $currentStatutId = null !== $currentStatut ? $currentStatut->getId() : null;
        $postStatutId = isset($postData['ID_STATUT']) ? self::toInt($postData['ID_STATUT']) : null;

        // Utiliser une comparaison non-stricte (==) comme le legacy pour couvrir le cas
        // où currentStatutId est null et postStatutId est non-null (statut assigné pour la première fois)
        if ($currentStatutId !== $postStatutId) {
            $alerte = TypeChangement::STATUT;
        }

        $currentCategorie = $informations->getCategorie();
        $currentCategorieId = null !== $currentCategorie ? $currentCategorie->getId() : null;
        $postCategorieId = isset($postData['ID_CATEGORIE']) ? self::toInt($postData['ID_CATEGORIE']) : null;

        $currentType = $informations->getType();
        $currentTypeId = null !== $currentType ? $currentType->getId() : null;
        $postTypeId = isset($postData['ID_TYPE']) ? self::toInt($postData['ID_TYPE']) : null;

        $currentActivite = $informations->getTypeActivite();
        $currentActiviteId = null !== $currentActivite ? $currentActivite->getId() : null;
        $postActiviteId = isset($postData['ID_TYPEACTIVITE']) ? self::toInt($postData['ID_TYPEACTIVITE']) : null;

        if ($currentCategorieId !== $postCategorieId
            || $currentTypeId !== $postTypeId
            || $currentActiviteId !== $postActiviteId
            || $this->compareActivitesSecondaires($informations, $postData)
        ) {
            return TypeChangement::CLASSEMENT;
        }

        return $alerte;
    }

    /**
     * Compare les activités secondaires actuelles de la fiche avec celles soumises.
     * Retourne true si une activité secondaire a été supprimée (déclenche une alerte classement).
     *
     * Port direct de Etablissement::compareActivitesSecondaires() du legacy.
     *
     * @param EtablissementInformations $informations La fiche courante
     * @param array<string, mixed>      $postData     Les données du formulaire POST
     */
    private function compareActivitesSecondaires(EtablissementInformations $informations, array $postData): bool
    {
        $postActivitesSecondaires = isset($postData['TYPES_ACTIVITES_SECONDAIRES']) && is_array($postData['TYPES_ACTIVITES_SECONDAIRES'])
            ? $postData['TYPES_ACTIVITES_SECONDAIRES']
            : [];

        foreach ($informations->getTypesActivitesSecondaires() as $activiteSecondaire) {
            $id = $activiteSecondaire->getId();
            if (null !== $id && !array_key_exists($id, $postActivitesSecondaires)) {
                return true;
            }
        }

        return false;
    }
}
