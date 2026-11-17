<?php

declare(strict_types=1);

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de suppression logique d'un dossier.
 *
 * Port direct de Service_Dossier::delete() et DossierController::deleteAction() (legacy).
 *
 * Note : les paramètres $date et $uniqueEtab de Service_Dossier::delete() ne sont pas portés
 * car ils constituent du code mort dans le contexte de deleteAction() :
 * - $uniqueEtab n'est passé à true que depuis deleteByEtab(), jamais depuis deleteAction()
 * - $date n'est jamais fourni depuis deleteAction() (new DateTime() en fallback immédiat)
 *
 * @see prevarisc/application/controllers/DossierController.php::deleteAction()
 * @see prevarisc/application/services/Dossier.php::delete()
 */
class DossierSuppressionService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DossierAffectationRepository $dossierAffectationRepository, private readonly DossierRepository $dossierRepository)
    {
    }

    /**
     * Effectue la suppression logique d'un dossier.
     *
     * Déroulement (iso-legacy) :
     * 1. Collecte les établissements liés non supprimés AVANT la suppression
     * 2. Supprime les affectations en commission (table dossieraffectation)
     * 3. Positionne la date de suppression et l'utilisateur supprimant
     * 4. Recalcule le dossier donnant avis pour chaque établissement lié
     * 5. Persiste l'ensemble en une seule transaction
     *
     * Le dossier courant est passé en exclusion à findLastDossierDonnantAvisForEtablissement()
     * car il n'est pas encore flush au moment de la requête (dateSuppression IS NULL en DB).
     * L'exclusion explicite garantit qu'il ne ressort pas comme « dernier donnant avis ».
     *
     * @return int[] IDs des établissements dont le dossier donnant avis a été recalculé
     */
    public function supprimer(Dossier $dossier, Utilisateur $user): array
    {
        // Collecte AVANT suppression (getEtablissements() filtre déjà dateSuppression IS NULL)
        $etablissements = $dossier->getEtablissements()->toArray();

        // Suppression DQL des affectations (exécution immédiate hors UoW)
        $this->dossierAffectationRepository->deleteAllAffectations($dossier);

        // Suppression logique
        $dossier->setDateSuppression(new \DateTime());
        $dossier->setDeletedBy($user);

        $this->entityManager->persist($dossier);

        // Recalcul du dossier donnant avis pour tous les établissements liés (iso-legacy)
        $etablissementsIds = [];
        foreach ($etablissements as $etablissement) {
            $dernierDossierDonnantAvis = $this->dossierRepository->findLastDossierDonnantAvisForEtablissement(
                $etablissement,
                $dossier
            );

            $etablissement->setDossierDonnantAvis($dernierDossierDonnantAvis);
            $this->entityManager->persist($etablissement);
            $etablissementsIds[] = $etablissement->getId();
        }

        $this->entityManager->flush();

        return $etablissementsIds;
    }
}
