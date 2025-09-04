<?php

namespace App\Service;

use App\Dto\Historique\Auteur;
use App\Dto\Historique\DonneeHistorique;
use App\Dto\Historique\Informations;
use App\Dto\Historique\LastAvis;
use App\Dto\Historique\LastInformations;
use App\Entity\Dossier;
use App\Entity\Etablissement as EtablissementEntity;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;

final class Etablissement
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    public function __construct(EtablissementRepository $etablissementRepository, DossierRepository $dossierRepository)
    {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
    }

    public function getHistorique(int $id): Informations
    {
        $etablissmentInformationsData = $this->etablissementRepository->getHistoriqueInformationsData($id);
        $etablissementDossiersData = $this->dossierRepository->getHistoriqueDossiersData($id);

        $historiqueInformations = $this->getHistoriqueInformations($etablissmentInformationsData);
        $historiqueDossiers = $this->getHistoriqueDossiers($etablissementDossiersData);
        $historiqueInformations->setAvis($historiqueDossiers);

        // @todo: Le traitement des avis derogations sera traitée dans l'onglet concerné
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
                $infosAuteur = new Auteur($auteur->getId(), $auteur->getNom().' '.$auteur->getPrenom());
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
                $infosAuteur = new Auteur($auteur->getId(), $auteur->getNom().' '.$auteur->getPrenom());
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
     * @return mixed[]
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
            if (!$texteApplicable->getVisible() || null === $texteApplicable->getType()) {
                continue;
            }

            if ($lastType === $texteApplicable->getType()) {
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
}
