<?php

namespace App\Service;

use App\Entity\Avis;
use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;

class DossierService
{
    /** @var DossierRepository */
    private $dossierRepository;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    public function __construct(
        DossierRepository $dossierRepository,
        EtablissementRepository $etablissementRepository
    ) {
        $this->dossierRepository = $dossierRepository;
        $this->etablissementRepository = $etablissementRepository;
    }

    /**
     * Récupère les informations des établissements associés à un dossier,
     * ou celles d'un établissement spécifique si l'ID est passé.
     *
     * @param Dossier $dossier
     * @param int|null $idEtablissement
     * @return array
     */
    public function getEtabInfos(Dossier $dossier, ?int $idEtablissement = null): array
    {
        // Si un ID d'établissement est fourni, on récupère l'établissement spécifique
        if ($idEtablissement) {
            $etablissement = $this->etablissementRepository->find($idEtablissement);
            if (!$etablissement) {
                throw new \Exception("Etablissement non trouvé.");
            }

            // On récupére les informations de l'établissement
            $etablissementInfo = $etablissement->getInformations();


            // On récupére l'avis d'exploitation si disponible
           $avisExploitation = null;
            if ($etablissement && $etablissement->getIdDossierDonnantAvis()) {
                $avisExploitation = $this->getAvisDossier($etablissement->getIdDossierDonnantAvis());
            }

            // Retourner les informations sous forme de tableau
            return [
                'etablissement' => $etablissement,
                'etablissementInfo' => $etablissementInfo,
                'avisExploitation' => $avisExploitation,
            ];
        }

        // Sinon, récupérer tous les établissements associés au dossier
        $etablissements = $dossier->getEtablissements();
        $resultat = [];

        foreach ($etablissements as $etablissement) {
            // On récupére les informations de chaque établissement
            $etablissementInfo = $etablissement->getInformations()->last();

            
            // On récupérer l'avis d'exploitation pour chaque établissement
            $avisExploitation = null;
            if ($etablissement && $etablissement->getIdDossierDonnantAvis() != null) {
                $avisExploitation = $this->getAvisDossier($etablissement->getIdDossierDonnantAvis());

            }

            // Ajouter à la liste des résultats
            $resultat[] = [
                'etablissement' => $etablissement,
                'etablissementInfo' => $etablissementInfo,
                'avisExploitation' => $avisExploitation,

            ];
        }
        return $resultat;
    }

    /**
     * Récupérer l'avis d'exploitation du dossier associé à l'ID donné.
     *
     * @param int $idDossierAvis
     * @return Avis|null
     */
    private function getAvisDossier(Dossier $idDossierAvis): ?Avis
    {
        $dossier = $this->dossierRepository->find($idDossierAvis);
        if (!$dossier) {
            return null;
        }
        //dd($dossier->getAvisDossierCommission());
        return $dossier->getAvisDossierCommission(); 
    }
}
