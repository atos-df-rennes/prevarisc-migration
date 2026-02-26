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
use App\Entity\PieceJointe;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Service\DataStore\DataStore;
use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    private $datastoreLogger;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        AvisDerogationsRepository $avisDerogationsRepository,
        EtablissementPieceJointeRepository $etablissementPJRepository,
        DataStore $dataStore,
        LoggerInterface $datastoreLogger,
        LoggerInterface $logger
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->avisDerogationsRepository = $avisDerogationsRepository;
        $this->etablissementPJRepository = $etablissementPJRepository;
        $this->dataStore = $dataStore;
        $this->datastoreLogger = $datastoreLogger;
        $this->logger = $logger;
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
                !\is_int($dossierAvisDerogation->getId())
                || !\is_int($avisDerogation->getId())
                || !\is_string($avisDerogation->getTitre())
                || !\is_string($avisDerogation->getType())
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
}
