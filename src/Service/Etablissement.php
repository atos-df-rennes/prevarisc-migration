<?php

namespace App\Service;

use App\Dto\AvisDerogationEtablissement;
use App\Dto\Historique\Auteur;
use App\Dto\Historique\DonneeHistorique;
use App\Dto\Historique\Informations;
use App\Dto\Historique\LastAvis;
use App\Dto\Historique\LastInformations;
use App\Entity\Dossier;
use App\Entity\Etablissement as EtablissementEntity;
use App\Entity\EtablissementPieceJointe;
use App\Entity\EtablissementStatut;
use App\Entity\Genre;
use App\Entity\PieceJointe;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\PieceJointeRepository;
use App\Service\DataStore\DataStore;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Etablissement
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var EtablissementPieceJointeRepository */
    private $etablissementPJRepository;

    /** @var DataStore */
    private $dataStore;

    /** @var AvisDerogationsRepository */
    private $avisDerogationsRepository;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /** @var LoggerInterface */
    private $datastoreLogger;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TexteApplicable */
    private $texteApplicableService;

    /** @var PieceJointeRepository */
    private $pieceJointeRepository;

    /** @var Image */
    private $imageService;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        AvisDerogationsRepository $avisDerogationsRepository,
        EtablissementPieceJointeRepository $etablissementPJRepository,
        DataStore $dataStore,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        LoggerInterface $datastoreLogger,
        LoggerInterface $logger,
        TexteApplicable $texteApplicableService,
        PieceJointeRepository $pieceJointeRepository,
        Image $imageService
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->avisDerogationsRepository = $avisDerogationsRepository;
        $this->etablissementPJRepository = $etablissementPJRepository;
        $this->dataStore = $dataStore;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->datastoreLogger = $datastoreLogger;
        $this->logger = $logger;
        $this->texteApplicableService = $texteApplicableService;
        $this->pieceJointeRepository = $pieceJointeRepository;
        $this->imageService = $imageService;
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

    public function getHistorique(int $id): Informations
    {
        $etablissmentInformationsData = $this->etablissementRepository->getHistoriqueInformationsData($id);
        $etablissementDossiersData = $this->dossierRepository->getHistoriqueDossiersData($id);

        $historiqueInformations = $this->getHistoriqueInformations($etablissmentInformationsData);
        $historiqueDossiers = $this->getHistoriqueDossiers($etablissementDossiersData);
        $historiqueInformations->setAvis($historiqueDossiers);

        return $historiqueInformations;
    }

    /**
     * Récupère les données d'historique des informations de l'établissement.
     */
    private function getHistoriqueInformations(EtablissementEntity $etablissement): Informations
    {
        $historique = new Informations();
        $lastHistorique = new LastInformations();

        foreach ($etablissement->getInformations() as $etablissementInformation) {
            $statut = $etablissementInformation->getStatut();
            $type = $etablissementInformation->getType();
            $categorie = $etablissementInformation->getCategorie();
            $libelle = $etablissementInformation->getLibelle();
            $date = $etablissementInformation->getDate();
            $classe = $etablissementInformation->getClasse();
            $famille = $etablissementInformation->getFamille();
            $auteur = $etablissementInformation->getUtilisateur();
            $infosAuteur = null;

            if (null === $date) {
                continue;
            }

            if (null !== $auteur) {
                $auteurInformations = $auteur->getInformations();

                if (null !== $auteurInformations) {
                    $infosAuteur = new Auteur($auteur->getId(), $auteurInformations->getNom().' '.$auteurInformations->getPrenom());
                }
            }

            if ($historique->isEmpty()) {
                $statutHistorique = new DonneeHistorique($date->getTimestamp(), null !== $statut ? $statut->getLibelle() : null, $infosAuteur);
                $historique->addStatut($statutHistorique);

                $typeHistorique = new DonneeHistorique($date->getTimestamp(), null !== $type ? $type->getLibelle() : null, $infosAuteur);
                $historique->addType($typeHistorique);

                $classeHistorique = new DonneeHistorique($date->getTimestamp(), null !== $classe ? $classe->getLibelle() : null, $infosAuteur);
                $historique->addClasse($classeHistorique);

                $familleHistorique = new DonneeHistorique($date->getTimestamp(), null !== $famille ? $famille->getLibelle() : null, $infosAuteur);
                $historique->addFamille($familleHistorique);

                $libelleHistorique = new DonneeHistorique($date->getTimestamp(), $libelle, $infosAuteur);
                $historique->addLibelle($libelleHistorique);

                $categorieHistorique = new DonneeHistorique($date->getTimestamp(), null !== $categorie ? $categorie->getLibelle() : null, $infosAuteur);
                $historique->addCategorie($categorieHistorique);

                $lastHistorique
                    ->setStatut($statut)
                    ->setType($type)
                    ->setCategorie($categorie)
                    ->setLibelle($libelle)
                    ->setClasse($classe)
                    ->setFamille($famille)
                ;
            } else {
                if ($lastHistorique->getStatut() !== $statut) {
                    $allStatuts = $historique->getStatut();
                    $currentStatut = end($allStatuts);
                    \assert(false !== $currentStatut);
                    $currentStatut->setFin($date->getTimestamp());

                    $lastHistorique->setStatut($statut);

                    $statutHistorique = new DonneeHistorique($date->getTimestamp(), null !== $statut ? $statut->getLibelle() : null, $infosAuteur);
                    $historique->addStatut($statutHistorique);
                }

                if ($lastHistorique->getType() !== $type) {
                    $allTypes = $historique->getType();
                    $currentType = end($allTypes);
                    \assert(false !== $currentType);
                    $currentType->setFin($date->getTimestamp());

                    $lastHistorique->setType($type);

                    $typeHistorique = new DonneeHistorique($date->getTimestamp(), null !== $type ? $type->getLibelle() : null, $infosAuteur);
                    $historique->addType($typeHistorique);
                }

                if ($lastHistorique->getLibelle() !== $libelle) {
                    $allLibelles = $historique->getLibelle();
                    $currentLibelle = end($allLibelles);
                    \assert(false !== $currentLibelle);
                    $currentLibelle->setFin($date->getTimestamp());

                    $lastHistorique->setLibelle($libelle);

                    $libelleHistorique = new DonneeHistorique($date->getTimestamp(), $libelle, $infosAuteur);
                    $historique->addLibelle($libelleHistorique);
                }

                if ($lastHistorique->getClasse() !== $classe) {
                    $allClasses = $historique->getClasse();
                    $currentClasse = end($allClasses);
                    \assert(false !== $currentClasse);
                    $currentClasse->setFin($date->getTimestamp());

                    $lastHistorique->setClasse($classe);

                    $classeHistorique = new DonneeHistorique($date->getTimestamp(), null !== $classe ? $classe->getLibelle() : null, $infosAuteur);
                    $historique->addClasse($classeHistorique);
                }

                if ($lastHistorique->getFamille() !== $famille) {
                    $allFamilles = $historique->getFamille();
                    $currentFamille = end($allFamilles);
                    \assert(false !== $currentFamille);
                    $currentFamille->setFin($date->getTimestamp());

                    $lastHistorique->setFamille($famille);

                    $familleHistorique = new DonneeHistorique($date->getTimestamp(), null !== $famille ? $famille->getLibelle() : null, $infosAuteur);
                    $historique->addFamille($familleHistorique);
                }

                if ($lastHistorique->getCategorie() !== $categorie) {
                    $allCategories = $historique->getCategorie();
                    $currentCategorie = end($allCategories);
                    \assert(false !== $currentCategorie);
                    $currentCategorie->setFin($date->getTimestamp());

                    $lastHistorique->setCategorie($categorie);

                    $categorieHistorique = new DonneeHistorique($date->getTimestamp(), null !== $categorie ? $categorie->getLibelle() : null, $infosAuteur);
                    $historique->addCategorie($categorieHistorique);
                }
            }
        }

        return $historique;
    }

    /**
     * Récupère les données d'historique des avis de l'établissement.
     *
     * @param Dossier[] $dossiers
     *
     * @return DonneeHistorique[]
     */
    private function getHistoriqueDossiers(array $dossiers): array
    {
        if ([] === $dossiers) {
            return [];
        }

        $historique = [];
        $lastHistorique = new LastAvis();

        foreach ($dossiers as $dossier) {
            $avis = $dossier->getAvisCommission();
            $date = null !== $dossier->getDateCommission() ? strtotime($dossier->getDateCommission()) : (null !== $dossier->getDateVisite() ? $dossier->getDateVisite()->getTimestamp() : false);
            $auteur = $dossier->getCreateur();
            $infosAuteur = null;

            if (null !== $auteur) {
                $auteurInformations = $auteur->getInformations();

                if (null !== $auteurInformations) {
                    $infosAuteur = new Auteur($auteur->getId(), $auteurInformations->getNom().' '.$auteurInformations->getPrenom());
                }
            }

            if ([] === $historique) {
                $avisHistorique = new DonneeHistorique($date, null !== $avis ? $avis->getLibelle() : null, $infosAuteur);
                $historique[] = $avisHistorique;

                $lastHistorique->setAvis($avis);
                $lastHistorique->setDate($date);
            } elseif ($lastHistorique->getAvis() !== $avis) {
                $currentLastAvis = end($historique);
                $currentLastAvis->setFin($date);

                $avisHistorique = new DonneeHistorique($date, null !== $avis ? $avis->getLibelle() : null, $infosAuteur);
                $historique[] = $avisHistorique;

                $lastHistorique
                    ->setAvis($avis)
                    ->setDate($date);
            }
        }

        return $historique;
    }

    /**
     * Récupère le dernier avis de l'établissement à partir de l'historique des dossiers.
     *
     * Retourne le libellé du dernier avis si disponible, sinon une chaîne vide.
     * Cette méthode est utilisée pour déterminer si la case "Diffère l'avis" doit être disabled.
     */
    public function getLastAvisLibelle(int $id): string
    {
        $etablissementDossiersData = $this->dossierRepository->getHistoriqueDossiersData($id);
        $historiqueDossiers = $this->getHistoriqueDossiers($etablissementDossiersData);

        if ([] === $historiqueDossiers) {
            return '';
        }

        $lastAvis = $historiqueDossiers[count($historiqueDossiers) - 1];

        return $lastAvis->getValeur() ?? '';
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
     * @return array<list{0:EtablissementPieceJointe,1:string}>
     */
    public function getPJImages(int $id): array
    {
        $images = $this->etablissementPJRepository->getPjByType($id, ['.jpg', '.jpeg', '.png', '.gif']);

        return $this->filtrePJs($images);
    }

    /**
     * @return array<list{0:EtablissementPieceJointe,1:string}>
     */
    public function getAutresPJs(int $id): array
    {
        $pjs = $this->etablissementPJRepository->getPjByType($id, ['.jpg', '.jpeg', '.png', '.gif'], true);

        return $this->filtrePJs($pjs);
    }

    /**
     * @param EtablissementPieceJointe[] $pjs
     *
     * @return array<list{0:EtablissementPieceJointe,1:string}>
     */
    private function filtrePJs(array $pjs): array
    {
        $store = $this->dataStore;
        $pjsFiltrees = array_filter(
            $pjs,
            function (EtablissementPieceJointe $pj) use ($store): bool {
                $path = '';

                if (null !== $pj->getPieceJointe() && null !== $pj->getEtablissement()) {
                    $path = $store->getFilePath($pj->getPieceJointe(), 'etablissement', $pj->getEtablissement()->getId());
                }

                if ('' === $path || !is_readable($path)) {
                    $piece = $pj->getPieceJointe();
                    $this->datastoreLogger->warning('La pièce jointe n\'est pas accessible en lecture.', [
                        'path' => $path,
                        'filename' => $piece instanceof PieceJointe ? $piece->getNom().$piece->getExtension() : 'Nom de la pièce inconnu',
                    ]);
                }

                return '' !== $path && is_readable($path);
            }
        );

        return array_map(function (EtablissementPieceJointe $pj) use ($store): array {
            $path = '';

            if (null !== $pj->getPieceJointe() && null !== $pj->getEtablissement()) {
                $path = $store->getURLPath($pj->getPieceJointe(), 'etablissement', $pj->getEtablissement()->getId());
            }

            return [$pj, $path ?? ''];
        }, $pjsFiltrees);
    }

    /**
     * @return AvisDerogationEtablissement[]
     */
    public function getAvisDerogations(EtablissementEntity $etablissement): array
    {
        $listeAvisDerogationsEtablissement = $this->avisDerogationsRepository->findForEtablissement($etablissement->getId());

        $filsEtablissement = $etablissement->getFils();
        foreach ($filsEtablissement as $fils) {
            $listeAvisDerogationsFils = $this->avisDerogationsRepository->findForEtablissement($fils->getId());
            $listeAvisDerogationsEtablissement = array_merge($listeAvisDerogationsEtablissement, $listeAvisDerogationsFils);
        }

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
     * Ajoute une pièce jointe à un établissement.
     *
     * @param EtablissementEntity $etablissement L'établissement auquel rattacher la pièce jointe
     * @param UploadedFile        $file          Le fichier uploadé
     * @param string              $name          Le libellé de la pièce jointe (si vide, utilise le nom du fichier)
     * @param string              $description   La description de la pièce jointe
     * @param int                 $mise_en_avant Le placement (0=liste, 1=diaporama, 2=plans)
     *
     * @throws \Exception Si le type de fichier est interdit ou si la miniature ne peut pas être créée
     */
    public function addPJ(EtablissementEntity $etablissement, UploadedFile $file, string $name, string $description, int $mise_en_avant): void
    {
        $extension = strtolower('.'.$file->getClientOriginalExtension());

        if (in_array($extension, ['.php', '.php4', '.php5', '.sh', '.ksh', '.csh'])) {
            throw new \Exception("Ce type de fichier n'est pas autorisé en upload");
        }

        $pieceJointe = new PieceJointe();
        $pieceJointe->setExtension($extension);
        $pieceJointe->setNom('' === $name ? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) : $name);
        $pieceJointe->setDescription($description);
        $pieceJointe->setDate(new \DateTime());

        $this->entityManager->persist($pieceJointe);
        $this->entityManager->flush();

        $filePath = $this->dataStore->getFilePath($pieceJointe, 'etablissement', $etablissement->getId(), true);
        $file->move(dirname($filePath), basename($filePath));

        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
        $placement = 0 !== $mise_en_avant && in_array($extension, $imageExtensions) ? $mise_en_avant : 0;

        $etablissementPJ = new EtablissementPieceJointe();
        $etablissementPJ->setEtablissement($etablissement);
        $etablissementPJ->setPieceJointe($pieceJointe);
        $etablissementPJ->setPlacement($placement);

        $this->entityManager->persist($etablissementPJ);
        $this->entityManager->flush();

        if (in_array($extension, $imageExtensions)) {
            $miniaturePjHelper = clone $pieceJointe;
            $miniaturePjHelper->setExtension('.jpg');
            $miniaturePath = $this->dataStore->getFilePath($miniaturePjHelper, 'etablissement_minature', $etablissement->getId(), true);
            $this->imageService->resize($filePath, 450)->save($miniaturePath);

            if (!is_file($miniaturePath)) {
                throw new \Exception('Cannot create miniature file: '.$miniaturePath);
            }
        }
    }

    /**
     * Supprime une pièce jointe d'un établissement.
     *
     * @param EtablissementEntity $etablissement L'établissement propriétaire de la pièce jointe
     * @param int                 $id_pj         L'identifiant de la pièce jointe à supprimer
     */
    public function deletePJ(EtablissementEntity $etablissement, int $id_pj): void
    {
        $pieceJointe = $this->pieceJointeRepository->find($id_pj);
        if (null === $pieceJointe) {
            return;
        }

        $etablissementPJ = $this->etablissementPJRepository->findOneBy([
            'etablissement' => $etablissement->getId(),
            'pieceJointe' => $id_pj,
        ]);

        $filePath = $this->dataStore->getFilePath($pieceJointe, 'etablissement', $etablissement->getId());

        $miniaturePjHelper = clone $pieceJointe;
        $miniaturePjHelper->setExtension('.jpg');

        $miniaturePath = $this->dataStore->getFilePath($miniaturePjHelper, 'etablissement_minature', $etablissement->getId());

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if (file_exists($miniaturePath)) {
            unlink($miniaturePath);
        }

        if (null !== $etablissementPJ) {
            $this->entityManager->remove($etablissementPJ);
        }

        $this->entityManager->remove($pieceJointe);
        $this->entityManager->flush();
    }
}
