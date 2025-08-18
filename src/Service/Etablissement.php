<?php

namespace App\Service;

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

    /**
     * @param int $id
     * @return mixed[]
     */
    public function getHistorique(int $id): array
    {
        $etablissmentInformationsData = $this->etablissementRepository->getHistoriqueInformationsData($id);
        $etablissementDossiersData = $this->dossierRepository->getHistoriqueDossiersData($id);

        $historiqueInformations = $this->getHistoriqueInformations($etablissmentInformationsData);
        $historiqueDossiers = $this->getHistoriqueDossiers($etablissementDossiersData);

        // @todo: Le traitement des avis derogations sera traitée dans l'onglet concerné
        return array_merge($historiqueInformations, $historiqueDossiers);
    }

    /**
     * @param EtablissementEntity $etablissement
     * @return mixed[]
     */
    private function getHistoriqueInformations(EtablissementEntity $etablissement): array
    {
        $lastHistorique = [];
        $historique = [];

        foreach ($etablissement->getInformations() as $etablissementInformation) {
            $statut = $etablissementInformation->getStatut();
            $type = $etablissementInformation->getType();
            $categorie = $etablissementInformation->getCategorie();
            $libelle = $etablissementInformation->getLibelle();
            $date = $etablissementInformation->getDate();
            $classe = $etablissementInformation->getClasse();
            $famille = $etablissementInformation->getFamille();
            $auteur = $etablissementInformation->getUtilisateur();
            $infosAuteur = [];

            if (null === $date) {
                continue;
            }

            if (null !== $auteur) {
                $infosAuteur = [
                    'id' => $auteur->getId(),
                    'name' => $auteur->getNom().' '.$auteur->getPrenom(),
                ];
            }

            if ([] === $historique) {
                $historique['statut'][$date->getTimestamp()] = [
                    'valeur' => null !== $statut ? $statut->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $historique['type'][$date->getTimestamp()] = [
                    'valeur' => null !== $type ? $type->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $historique['classe'][$date->getTimestamp()] = [
                    'valeur' => null !== $classe ? $classe->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $historique['famille'][$date->getTimestamp()] = [
                    'valeur' => null !== $famille ? $famille->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $historique['libelle'][$date->getTimestamp()] = [
                    'valeur' => $libelle,
                    'auteur' => $infosAuteur,
                ];
                $historique['categorie'][$date->getTimestamp()] = [
                    'valeur' => null !== $categorie ? $categorie->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $lastHistorique = [
                    'statut' => $statut,
                    'type' => $type,
                    'categorie' => $categorie,
                    'libelle' => $libelle,
                    'classe' => $classe,
                    'famille' => $famille,
                    'date' => $date->getTimestamp(),
                ];
            } else {
                $historized = false;

                if ($lastHistorique['statut'] !== $statut) {
                    $historique['statut'][$lastHistorique['date']]['fin'] = $date->getTimestamp();
                    $historique['statut'][$date->getTimestamp()] = [
                        'valeur' => isset($statut) ? $statut->getLibelle() : '',
                        'auteur' => $infosAuteur,
                    ];
                    $lastHistorique['statut'] = $statut;
                    $historized = true;
                }

                if ($lastHistorique['type'] !== $type) {
                    $historique['type'][$lastHistorique['date']]['fin'] = $date->getTimestamp();
                    $historique['type'][$date->getTimestamp()] = [
                        'valeur' => isset($type) ? $type->getLibelle() : '',
                        'auteur' => $infosAuteur,
                    ];
                    $lastHistorique['type'] = $type;
                    $historized = true;
                }

                if ($lastHistorique['libelle'] !== $libelle) {
                    $historique['libelle'][$lastHistorique['date']]['fin'] = $date->getTimestamp();
                    $historique['libelle'][$date->getTimestamp()] = [
                        'valeur' => $libelle,
                        'auteur' => $infosAuteur,
                    ];
                    $lastHistorique['libelle'] = $libelle;
                    $historized = true;
                }

                if ($lastHistorique['classe'] !== $classe) {
                    $historique['classe'][$lastHistorique['date']]['fin'] = $date->getTimestamp();
                    $historique['classe'][$date->getTimestamp()] = [
                        'valeur' => isset($classe) ? $classe->getLibelle() : '',
                        'auteur' => $infosAuteur,
                    ];
                    $lastHistorique['classe'] = $classe;
                    $historized = true;
                }

                if ($lastHistorique['famille'] !== $famille) {
                    $historique['famille'][$lastHistorique['date']]['fin'] = $date->getTimestamp();
                    $historique['famille'][$date->getTimestamp()] = [
                        'valeur' => isset($famille) ? $famille->getLibelle() : '',
                        'auteur' => $infosAuteur,
                    ];
                    $lastHistorique['famille'] = $famille;
                    $historized = true;
                }

                if ($lastHistorique['categorie'] !== $categorie) {
                    $historique['categorie'][$lastHistorique['date']]['fin'] = $date->getTimestamp();
                    $historique['categorie'][$date->getTimestamp()] = [
                        'valeur' => isset($categorie) ? $categorie->getLibelle() : '',
                        'auteur' => $infosAuteur,
                    ];
                    $lastHistorique['categorie'] = $categorie;
                    $historized = true;
                }

                if ($historized) {
                    $lastHistorique['date'] = $date->getTimestamp();
                }
            }
        }

        return $historique;
    }

    /**
     * @param \App\Entity\Dossier[] $dossiers
     */
    private function getHistoriqueDossiers(array $dossiers): array
    {
        if ([] === $dossiers) {
            return [];
        }

        $historique = [];
        $lastHistorique = [];

        foreach ($dossiers as $dossier) {
            $avis = $dossier->getAvisCommission();
            $date = null !== $dossier->getDateCommission() ? strtotime($dossier->getDateCommission()) : (null !== $dossier->getDateVisite() ? $dossier->getDateVisite()->getTimestamp() : '');
            $auteur = $dossier->getCreateur();
            $infosAuteur = [];

            if (null !== $auteur) {
                $infosAuteur = [
                    'id' => $auteur->getId(),
                    'name' => $auteur->getNom().' '.$auteur->getPrenom(),
                ];
            }

            if ([] === $historique) {
                $historique['avis'][$date] = [
                    'valeur' => null !== $avis ? $avis->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $lastHistorique = [
                    'avis' => $avis,
                    'date' => $date,
                ];
            } elseif ($lastHistorique['avis'] !== $avis) {
                $historique['avis'][$lastHistorique['date']]['fin'] = $date;
                $historique['avis'][$date] = [
                    'valeur' => null !== $avis ? $avis->getLibelle() : '',
                    'auteur' => $infosAuteur,
                ];
                $lastHistorique['avis'] = $avis;
                $lastHistorique['date'] = $date;
            }
        }

        return $historique;
    }
}
