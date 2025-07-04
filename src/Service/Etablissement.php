<?php

namespace App\Service;

use App\Repository\EtablissementRepository;

final class Etablissement
{
    /** @var EtablissementRepository */
    private $repository;

    public function __construct(EtablissementRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getHistorique(int $id): array
    {
        $historique = [];
        $etablissementInformations = $this->repository->getHistorique($id);

        dump($etablissementInformations);
        die;

        foreach ($etablissementInformations as $etablissementInformation) {
//            $statut = $etablissementInformation->getStatut();
//            $type = $etablissementInformation->getType();
//            $categorie = $etablissementInformation->getCategorie();
//            $libelle = $etablissementInformation->getLibelle();
//            $date = $etablissementInformation->getDate();
            if (count($historique) === 0) {
//                $historique[$date] = [
//                    'statut' => $statut,
//                    'type' => $type,
//                    'categorie' => $categorie,
//                    'libelle' => $libelle,
//                    'classe' => '',
//                    'famille' => '',
//                    'auteur' => ''
//                ];
            }
            else {
                // TODO enregistrer uniquement les diff + auteur de la diff
            }
        }

        // TODO traitement des dossiers pour les avis
        // TODO traitement avis_derogations
    }

}
