<?php

namespace App\Service;

use App\Constant\GenreEtablissement;
use App\Dto\AvisDerogationEtablissement;
use App\Entity\AdresseRue;
use App\Entity\Classement;
use App\Entity\Commission;
use App\Entity\Commune;
use App\Entity\Etablissement as EtablissementEntity;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\EtablissementInformationsPlan;
use App\Entity\EtablissementInformationsRubriques;
use App\Entity\EtablissementInformationsTypesActivitesSecondaires;
use App\Entity\EtablissementStatut;
use App\Entity\EtablissementType;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\IghClasse;
use App\Entity\TypePlan;
use App\Entity\Utilisateur;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
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

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        AvisDerogationsRepository $avisDerogationsRepository,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        TexteApplicable $texteApplicableService
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->avisDerogationsRepository = $avisDerogationsRepository;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->texteApplicableService = $texteApplicableService;
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
     * Convertit de manière sécurisée une valeur de type mixed en flottant.
     *
     * @param mixed $val
     */
    private static function toFloat($val, float $default = 0.0): float
    {
        return is_scalar($val) ? (float) $val : $default;
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

            // Remise à zéro des champs genre-spécifiques
            $informations->setIcpe(false);
            $informations->setR14320(false);
            $informations->setDroitPublic(false);
            $informations->setLocalSommeil(false);
            $informations->setEffectifPublic(0);
            $informations->setEffectifPersonnel(0);
            $informations->setEffectifHeberge(0);
            $informations->setEffectifJustifiantClassement(0);
            $informations->setEffectifHabitation(0);
            $informations->setEffectifCaravane(0);
            $informations->setEffectifEmplacementNu(0);
            $informations->setEffectifDivers(0);

            // --- Switch genre ---
            switch ($idGenre) {
                // ERP
                case GenreEtablissement::ERP:
                    $rawCategorieId = self::toString($data['ID_CATEGORIE'] ?? '');
                    if ('' !== $rawCategorieId) {
                        /** @var EtablissementCategorie $cat */
                        $cat = $em->getReference(EtablissementCategorie::class, self::toInt($rawCategorieId));
                        $informations->setCategorie($cat);
                    }

                    $rawPeriodicite = self::toString($data['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? '');
                    if ('' !== $rawPeriodicite) {
                        $informations->setPeriodicite(self::toInt($rawPeriodicite));
                    }

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
                    if ('' !== $rawCategorieId && 5 === self::toInt($rawCategorieId)) {
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

                    $etablissement->setNbPrev(
                        isset($data['NBPREV_ETABLISSEMENT']) ? self::toInt($data['NBPREV_ETABLISSEMENT']) : null
                    );
                    if (!empty($data['DUREEVISITE_ETABLISSEMENT'])) {
                        $duree = \DateTime::createFromFormat('H:i', self::toString($data['DUREEVISITE_ETABLISSEMENT']));
                        if ($duree instanceof \DateTime) {
                            $etablissement->setDureeVisite($duree);
                        }
                    }

                    break;

                    // Cellule
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
                    $etablissement->setNbPrev(
                        isset($data['NBPREV_ETABLISSEMENT']) ? self::toInt($data['NBPREV_ETABLISSEMENT']) : null
                    );
                    if (!empty($data['DUREEVISITE_ETABLISSEMENT'])) {
                        $duree = \DateTime::createFromFormat('H:i', self::toString($data['DUREEVISITE_ETABLISSEMENT']));
                        if ($duree instanceof \DateTime) {
                            $etablissement->setDureeVisite($duree);
                        }
                    }

                    break;

                    // Habitation
                case GenreEtablissement::HABITATION:
                    $rawFamilleId = self::toString($data['ID_FAMILLE'] ?? '');
                    if ('' !== $rawFamilleId) {
                        /** @var Famille $famille */
                        $famille = $em->getReference(Famille::class, self::toInt($rawFamilleId));
                        $informations->setFamille($famille);
                    }

                    break;

                    // IGH
                case GenreEtablissement::IGH:
                    $rawClasseId = self::toString($data['ID_CLASSE'] ?? '');
                    if ('' !== $rawClasseId) {
                        /** @var IghClasse $classe */
                        $classe = $em->getReference(IghClasse::class, self::toInt($rawClasseId));
                        $informations->setClasse($classe);
                    }

                    $rawPeriodicite = self::toString($data['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? '');
                    if ('' !== $rawPeriodicite) {
                        $informations->setPeriodicite(self::toInt($rawPeriodicite));
                    }

                    $informations->setEffectifPublic(self::toInt($data['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0));
                    $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));
                    $rawCommissionId = self::toString($data['ID_COMMISSION'] ?? '');
                    if ('' !== $rawCommissionId) {
                        /** @var Commission $commission */
                        $commission = $em->getReference(Commission::class, self::toInt($rawCommissionId));
                        $informations->setCommission($commission);
                    }

                    $etablissement->setNbPrev(
                        isset($data['NBPREV_ETABLISSEMENT']) ? self::toInt($data['NBPREV_ETABLISSEMENT']) : null
                    );
                    if (!empty($data['DUREEVISITE_ETABLISSEMENT'])) {
                        $duree = \DateTime::createFromFormat('H:i', self::toString($data['DUREEVISITE_ETABLISSEMENT']));
                        if ($duree instanceof \DateTime) {
                            $etablissement->setDureeVisite($duree);
                        }
                    }

                    break;

                    // BUP
                case GenreEtablissement::BUP:
                    $informations->setIcpe(!empty($data['ICPE_ETABLISSEMENTINFORMATIONS']));
                    $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));

                    break;

                    // Camping
                case GenreEtablissement::CAMPING:
                    $informations->setEffectifCaravane(self::toInt($data['EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS'] ?? 0));
                    $informations->setEffectifHabitation(self::toInt($data['EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS'] ?? 0));
                    $informations->setEffectifEmplacementNu(self::toInt($data['EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS'] ?? 0));
                    $informations->setEffectifDivers(self::toInt($data['EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS'] ?? 0));

                    break;

                    // Manifestation temporaire — fall-through vers IOP
                case GenreEtablissement::MANIFESTATION_TEMPORAIRE:
                    // IOP
                case GenreEtablissement::IOP:
                    $informations->setEffectifPublic(self::toInt($data['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0));
                    $informations->setEffectifPersonnel(self::toInt($data['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0));

                    break;

                    // Zone
                case GenreEtablissement::ZONE:
                    $rawClassementId = self::toString($data['ID_CLASSEMENT'] ?? '');
                    if ('' !== $rawClassementId) {
                        /** @var Classement $classement */
                        $classement = $em->getReference(Classement::class, self::toInt($rawClassementId));
                        $informations->setClassement($classement);
                    }

                    break;

                    // Site (case 1) et autres genres : aucun champ spécifique
                default:
                    break;
            }

            $em->persist($informations);

            // --- Préventionnistes ---
            if (!empty($data['ID_UTILISATEUR']) && is_array($data['ID_UTILISATEUR'])) {
                foreach ($data['ID_UTILISATEUR'] as $idPreventionniste) {
                    $idPreventionniste = self::toInt($idPreventionniste);
                    if ($idPreventionniste > 0) {
                        /** @var Utilisateur $prev */
                        $prev = $em->getReference(Utilisateur::class, $idPreventionniste);
                        $informations->addPreventionniste($prev);
                    }
                }
            }

            // --- Rubriques ICPE (BUP uniquement) ---
            if (GenreEtablissement::BUP === $idGenre && !empty($data['RUBRIQUES']) && is_array($data['RUBRIQUES'])) {
                foreach ($data['RUBRIQUES'] as $key => $rubriqueData) {
                    if (0 === self::toInt($key)) {
                        continue;
                    }

                    if (!is_array($rubriqueData)) {
                        continue;
                    }

                    $rubrique = new EtablissementInformationsRubriques();
                    $rubrique->setRubrique(self::toInt($rubriqueData['ID_RUBRIQUE'] ?? 0));
                    $rubrique->setNumero(self::toInt($rubriqueData['NUMERO_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? 0));
                    $rubrique->setValeur(self::toFloat($rubriqueData['VALEUR_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? 0.0));
                    $rubrique->setNom(self::toString($rubriqueData['NOM_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? ''));
                    $rubrique->setClassement(self::toString($rubriqueData['CLASSEMENT_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? ''));
                    $rubrique->setEtablissementInformations($informations);
                    $em->persist($rubrique);
                }
            }

            // --- Plans (ERP, Cellule, IGH, BUP, Camping, Manifestation temporaire, IOP) ---
            $genresAvecPlans = [
                GenreEtablissement::ERP,
                GenreEtablissement::CELLULE,
                GenreEtablissement::IGH,
                GenreEtablissement::BUP,
                GenreEtablissement::CAMPING,
                GenreEtablissement::MANIFESTATION_TEMPORAIRE,
                GenreEtablissement::IOP,
            ];
            if (in_array($idGenre, $genresAvecPlans, true) && !empty($data['PLANS']) && is_array($data['PLANS'])) {
                foreach ($data['PLANS'] as $key => $planData) {
                    if (0 === self::toInt($key)) {
                        continue;
                    }

                    if (!is_array($planData)) {
                        continue;
                    }

                    if (empty($planData['ID_TYPEPLAN'])) {
                        continue;
                    }

                    $plan = new EtablissementInformationsPlan();
                    /** @var TypePlan $typePlan */
                    $typePlan = $em->getReference(TypePlan::class, self::toInt($planData['ID_TYPEPLAN']));
                    $plan->setType($typePlan);
                    $rawNumPlan = $planData['NUMERO_ETABLISSEMENTPLAN'] ?? null;
                    $plan->setNumero(null !== $rawNumPlan ? self::toString($rawNumPlan) : null);
                    $rawDatePlan = $planData['DATE_ETABLISSEMENTPLAN'] ?? '';
                    $datePlan = ('' !== self::toString($rawDatePlan))
                        ? new \DateTime(self::toString($rawDatePlan))
                        : new \DateTime('today');
                    $plan->setDate($datePlan);
                    $plan->setAMettreAJour(!empty($planData['MISEAJOUR_ETABLISSEMENTPLAN']));
                    $plan->setEtablissementInformations($informations);
                    $em->persist($plan);
                }
            }

            // --- Activités secondaires (ERP et Cellule) ---
            if (in_array($idGenre, [GenreEtablissement::ERP, GenreEtablissement::CELLULE], true)
                && !empty($data['TYPES_ACTIVITES_SECONDAIRES'])
                && is_array($data['TYPES_ACTIVITES_SECONDAIRES'])
            ) {
                foreach ($data['TYPES_ACTIVITES_SECONDAIRES'] as $key => $taData) {
                    if (0 === self::toInt($key)) {
                        continue;
                    }

                    if (!is_array($taData)) {
                        continue;
                    }

                    $ta = new EtablissementInformationsTypesActivitesSecondaires();
                    /** @var EtablissementType $typeSecondaire */
                    $typeSecondaire = $em->getReference(EtablissementType::class, self::toInt($taData['ID_TYPE_SECONDAIRE'] ?? 0));
                    /** @var EtablissementActivite $activiteSecondaire */
                    $activiteSecondaire = $em->getReference(EtablissementActivite::class, self::toInt($taData['ID_TYPEACTIVITE_SECONDAIRE'] ?? 0));
                    $ta->setType($typeSecondaire);
                    $ta->setActivite($activiteSecondaire);
                    $ta->setEtablissementInformations($informations);
                    $em->persist($ta);
                }
            }

            // --- Adresses (ERP, Habitation, IGH, BUP, Camping, Manifestation, IOP, Zone) ---
            $genresAvecAdresse = [
                GenreEtablissement::ERP,
                GenreEtablissement::HABITATION,
                GenreEtablissement::IGH,
                GenreEtablissement::BUP,
                GenreEtablissement::CAMPING,
                GenreEtablissement::MANIFESTATION_TEMPORAIRE,
                GenreEtablissement::IOP,
                GenreEtablissement::ZONE,
            ];
            if (in_array($idGenre, $genresAvecAdresse, true) && !empty($data['ADRESSES']) && is_array($data['ADRESSES'])) {
                foreach ($data['ADRESSES'] as $key => $adresseData) {
                    if (0 === self::toInt($key)) {
                        continue;
                    }

                    if (!is_array($adresseData)) {
                        continue;
                    }

                    $rawRueId = self::toInt($adresseData['ID_RUE'] ?? 0);
                    if ($rawRueId <= 0) {
                        continue;
                    }

                    $adresse = new EtablissementAdresse();
                    /** @var AdresseRue $rue */
                    $rue = $em->getReference(AdresseRue::class, $rawRueId);
                    $adresse->setRue($rue);
                    /** @var Commune $commune */
                    $commune = $em->getReference(Commune::class, self::toString($adresseData['NUMINSEE_COMMUNE'] ?? ''));
                    $adresse->setCommune($commune);
                    $adresse->setEtablissement($etablissement);
                    $rawNum = self::toString($adresseData['NUMERO_ADRESSE'] ?? '');
                    $adresse->setNumero('' !== $rawNum ? $rawNum : null);
                    $rawCompl = self::toString($adresseData['COMPLEMENT_ADRESSE'] ?? '');
                    $adresse->setComplement('' !== $rawCompl ? $rawCompl : null);
                    $rawLon = $adresseData['LON_ETABLISSEMENTADRESSE'] ?? '';
                    $adresse->setLongitude(!empty($rawLon) ? self::toFloat($rawLon) : null);
                    $rawLat = $adresseData['LAT_ETABLISSEMENTADRESSE'] ?? '';
                    $adresse->setLatitude(!empty($rawLat) ? self::toFloat($rawLat) : null);
                    $em->persist($adresse);
                }
            }

            // --- Établissements fils ---
            if (!empty($data['ID_FILS_ETABLISSEMENT']) && is_array($data['ID_FILS_ETABLISSEMENT'])) {
                foreach ($data['ID_FILS_ETABLISSEMENT'] as $idFils) {
                    $idFils = self::toInt($idFils);
                    if ($idFils <= 0) {
                        continue;
                    }

                    $fils = $this->etablissementRepository->find($idFils);
                    if (null === $fils) {
                        continue;
                    }

                    $filsGenre = $fils->getLatestInformations()->getGenre();
                    $filsGenreId = null !== $filsGenre ? $filsGenre->getId() : null;

                    // Validations de compatibilité genre
                    if (GenreEtablissement::SITE === $idGenre
                        && !in_array($filsGenreId, [GenreEtablissement::ERP, GenreEtablissement::HABITATION, GenreEtablissement::IGH, GenreEtablissement::BUP, GenreEtablissement::CAMPING, GenreEtablissement::MANIFESTATION_TEMPORAIRE, GenreEtablissement::IOP], true)
                    ) {
                        throw new \InvalidArgumentException('L\'établissement enfant n\'est pas compatible avec un site.', 500);
                    }

                    if (in_array($idGenre, [GenreEtablissement::ERP, GenreEtablissement::IGH], true)
                        && GenreEtablissement::CELLULE !== $filsGenreId
                    ) {
                        throw new \InvalidArgumentException('Un ERP ou IGH ne peut contenir que des cellules.', 500);
                    }

                    if (GenreEtablissement::SITE === $filsGenreId) {
                        throw new \InvalidArgumentException('Un site ne peut pas être enfant d\'un autre établissement.', 500);
                    }

                    $etablissement->addFils($fils);

                    // Propagation de la catégorie aux cellules
                    if (GenreEtablissement::CELLULE === $filsGenreId) {
                        $filsInfo = $fils->getLatestInformations();
                        $filsInfo->setCategorie($informations->getCategorie());
                    }
                }
            }

            // --- Père de l'établissement ---
            if (!empty($data['ID_PERE'])) {
                $idPere = self::toInt($data['ID_PERE']);
                $pere = $this->etablissementRepository->find($idPere);
                if (null !== $pere) {
                    $pereGenre = $pere->getLatestInformations()->getGenre();
                    $pereGenreId = null !== $pereGenre ? $pereGenre->getId() : null;

                    if (in_array($idGenre, [GenreEtablissement::ERP, GenreEtablissement::HABITATION, GenreEtablissement::IGH, GenreEtablissement::BUP, GenreEtablissement::CAMPING, GenreEtablissement::MANIFESTATION_TEMPORAIRE, GenreEtablissement::IOP], true)
                        && GenreEtablissement::SITE !== $pereGenreId
                    ) {
                        throw new \InvalidArgumentException('L\'établissement père doit être un site.', 500);
                    }

                    if (GenreEtablissement::CELLULE === $idGenre
                        && !in_array($pereGenreId, [GenreEtablissement::ERP, GenreEtablissement::IGH], true)
                    ) {
                        throw new \InvalidArgumentException('Une cellule doit avoir pour père un ERP ou un IGH.', 500);
                    }

                    if (GenreEtablissement::SITE === $idGenre) {
                        throw new \InvalidArgumentException('Un site ne peut pas avoir de père.', 500);
                    }

                    $etablissement->addParent($pere);

                    // Propagation de la catégorie de l'ERP/IGH parent vers la cellule
                    if (GenreEtablissement::CELLULE === $idGenre) {
                        $pereCategorie = $pere->getLatestInformations()->getCategorie();
                        $informations->setCategorie($pereCategorie);
                    }
                }
            }

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
}
