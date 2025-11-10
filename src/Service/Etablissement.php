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
use App\Entity\Genre;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Service\DataStore\DataStore;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        AvisDerogationsRepository $avisDerogationsRepository,
        EtablissementPieceJointeRepository $etablissementPJRepository,
        DataStore $dataStore,
        ParameterBagInterface $parameterBag
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->avisDerogationsRepository = $avisDerogationsRepository;
        $this->etablissementPJRepository = $etablissementPJRepository;
        $this->dataStore = $dataStore;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Récupération d'un établissement avec toutes ses informations connexes.
     *
     * @throws \Exception si l'établissement n'existe pas ou si la dernière fiche d'informations n'existe pas
     */
    public function get(int $idEtablissement): array
    {
        // Récupération de l'établissement avec toutes les données jointes
        $etablissement = $this->etablissementRepository->findByIdWithJoinedData($idEtablissement);

        // Si l'établissement n'existe pas ou est supprimé, on génère une erreur
        if (!$etablissement || null !== $etablissement->getDateSuppression()) {
            throw new \Exception("L'établissement n'existe pas.");
        }

        // On récupère la dernière fiche d'informations de l'établissement
        $informations = $etablissement->getLatestInformations();
        if (!$informations) {
            throw new \Exception("La fiche d'informations de l'établissement n'existe pas.");
        }

        $result = [
            'etablissement' => $etablissement,
            'facteurDangerositeId' => null,
            'avisId' => null,
            'lastVisite' => null,
            'nextVisite' => null,
            'pcInitial' => null,
        ];

        // Récupération de l'avis de l'établissement
        $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();
        if ($dossierDonnantAvis instanceof Dossier) {
            $avis = $dossierDonnantAvis->getAvisCommission();
            $result['facteurDangerositeId'] = $dossierDonnantAvis->getFacteurDangerosite();
            $result['avisId'] = $avis ? $avis->getId() : null;
        }

        // Récupération des dates de visite
        $useDateCommissionForPeriodicity = $this->parameterBag->get('app.relance_periodicite_commission');
        $lastVisiteDossier = $this->dossierRepository->getLastVisitePeriodique($idEtablissement, $useDateCommissionForPeriodicity);

        if ($lastVisiteDossier && $lastVisiteDossier['date_relance']) {
            $dateLastVisite = $lastVisiteDossier['date_relance'];
            $result['lastVisite'] = $dateLastVisite;

            $periodicite = $informations->getPeriodicite();
            if ($periodicite && $periodicite > 0) {
                $dateNextVisite = clone $dateLastVisite;
                $dateNextVisite->modify(sprintf('+%d months', $periodicite));
                $result['nextVisite'] = $dateNextVisite;
            }
        }

        // Récupération de la date de PC initial
        $pcInitialDossier = $this->dossierRepository->getPCInitial($idEtablissement);
        if ($pcInitialDossier) {
            $result['pcInitial'] = $pcInitialDossier['date'];
        }

        // Présence d'avis différé
        $result['presenceAvisDiffere'] = $this->dossierRepository->hasAvisDiffere($idEtablissement);

        // Présence d'écheancier
        $result['presenceEcheancier'] = $this->dossierRepository->hasEcheancier($idEtablissement);

        // Calcul de la périodicité
        $result['periodiciteCalculee'] = $this->calculatePeriodiciteForEntity($etablissement);

        // Calcul des données pratiques (nbPrev et dureeVisite)
        $donneesPratiques = $this->calculateDonneesPratiques($etablissement);
        $result['nbPrevCalcule'] = $donneesPratiques['nbPrevCalcule'];
        $result['dureeVisiteCalculee'] = $donneesPratiques['dureeVisiteCalculee'];

        // Récupération de l'indicateur de présence d'un DUS (ID_FONCTION = 8)
        $result['presenceDus'] = $this->hasContactDus($etablissement);

        return $result;
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
        $textesApplicables = [];
        $lastType = '';

        foreach ($textesApplicablesValues as $texteApplicable) {
            if (!$texteApplicable->getVisible()) {
                continue;
            }

            if (null === $texteApplicable->getType()) {
                continue;
            }

            if ('' !== $lastType && $lastType === $texteApplicable->getType()) {
                // @fixme: Créer un DTO pour corriger l'erreur PHPStan
                /* @phpstan-ignore-next-line */
                $textesApplicables[$texteApplicable->getType()->getId()]['textes'][] = $texteApplicable->getLibelle();

                continue;
            }

            $textesApplicables[$texteApplicable->getType()->getId()] = [
                'titre' => $texteApplicable->getType()->getLibelle(),
                'textes' => [$texteApplicable->getLibelle()],
            ];
            $lastType = $texteApplicable->getType();
        }

        return $textesApplicables;
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
    public function getAvisDerogations(int $id): array
    {
        $listeAvisDerogationsEtablissement = $this->etablissementRepository->getAvisDerogationsEtablissement($id);

        $avisDerogations = [];
        foreach ($listeAvisDerogationsEtablissement as $dossier) {
            $avisDerogation = $this->avisDerogationsRepository->find($dossier['idAvisDerogation']);
            $leve = false;
            $debut = null;
            $typeDossier = null;

            if (null !== $avisDerogation && null !== $avisDerogation->getDossierLie()) {
                if (null !== $avisDerogation->getDossierLie()->getType() && null !== $avisDerogation->getDossierLie()->getType()->getId()) {
                    $typeDossier = $avisDerogation->getDossierLie()->getType()->getId();
                }

                $leve = true;
            }

            if (null !== $dossier['dateCommission']) {
                $debut = $dossier['dateCommission'];
            } elseif (null !== $dossier['dateVisite']) {
                $debut = $dossier['dateVisite'];
            }

            $avisDerogations[] = new AvisDerogationEtablissement(
                $dossier['id'],
                $dossier['idAvisDerogation'],
                $dossier['titre'],
                $dossier['type'],
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
     * @return string la chaîne représentant la périodicité au format "X mois", "X ans", ou "X ans et Y mois"
     */
    public function getDisplayedPeriodicity(EtablissementEntity $etablissement): string
    {
        $numberOfMonthsInAYear = 12;

        $information = $etablissement->getLatestInformations();

        $periodicity = $information->getPeriodicite();
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
     * Calcule la périodicité pour une entité établissement.
     */
    private function calculatePeriodiciteForEntity(EtablissementEntity $etablissement): ?int
    {
        $informations = $etablissement->getLatestInformations();
        $genre = $informations->getGenre();
        $genreId = $genre instanceof Genre ? $genre->getId() : null;
        $periodicite = $informations->getPeriodicite();

        // Site (genre 1)
        if (1 === $genreId) {
            foreach ($etablissement->getFils() as $enfant) {
                $enfantInfos = $enfant->getLatestInformations();
                if (!$enfantInfos) {
                    continue;
                }

                $enfantGenre = $enfantInfos->getGenre();
                if (!$enfantGenre) {
                    continue;
                }

                if (2 !== $enfantGenre->getId()) {
                    continue;
                }

                $periodiciteEnfant = $enfantInfos->getPeriodicite();
                if (null === $periodiciteEnfant) {
                    continue;
                }

                if (0 === $periodiciteEnfant) {
                    continue;
                }

                $enfantStatut = $enfantInfos->getStatut();
                if (!$enfantStatut) {
                    continue;
                }

                if (2 !== $enfantStatut->getId()) {
                    continue;
                }

                if (null === $periodicite || $periodicite > $periodiciteEnfant) {
                    $periodicite = $periodiciteEnfant;
                }
            }
        }

        // Cellule (genre 3) avec parents
        if (3 === $genreId && null !== $etablissement->getParent()) {
            $currentEtablissement = $etablissement;
            while (null !== $currentEtablissement->getParent()) {
                $currentEtablissement = $currentEtablissement->getParent();
            }

            $lastParentInfos = $currentEtablissement->getLatestInformations();
            if ($lastParentInfos) {
                $periodicite = $lastParentInfos->getPeriodicite();
            }
        }

        return $periodicite;
    }

    /**
     * Calcule les données pratiques selon le genre de l'établissement.
     *
     * @return array{nbPrevCalcule: ?int, dureeVisiteCalculee: ?string}
     */
    private function calculateDonneesPratiques(EtablissementEntity $etablissement): array
    {
        $informations = $etablissement->getLatestInformations();
        $genre = $informations->getGenre();
        $genreId = $genre instanceof Genre ? $genre->getId() : null;

        // Site (genre 1)
        if (1 === $genreId) {
            $dureeTotale = 0;
            foreach ($etablissement->getFils() as $enfant) {
                $dureeVisite = $enfant->getDureeVisite();
                $nbPrev = $enfant->getNbPrev();

                if ($dureeVisite && $nbPrev) {
                    $dureeEtablissement = strtotime($dureeVisite) - strtotime('00:00:00');
                    $dureeTotale += $nbPrev * $dureeEtablissement;
                }
            }

            return [
                'nbPrevCalcule' => null,
                'dureeVisiteCalculee' => gmdate('H:i:s', $dureeTotale),
            ];
        }

        // Cellule (genre 3) ou autres genres
        return [
            'nbPrevCalcule' => $etablissement->getNbPrev(),
            'dureeVisiteCalculee' => $etablissement->getDureeVisite(),
        ];
    }

    /**
     * Vérifie la présence d'un DUS (ID_FONCTION = 8) dans les contacts.
     */
    private function hasContactDus(EtablissementEntity $etablissement): bool
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
}
