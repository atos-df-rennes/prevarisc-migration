<?php

namespace App\Service;

use App\Entity\Dossier as DossierEntity;
use App\Entity\Utilisateur;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

final class Dossier
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var Security */
    private $security;

    /** @var PlatauTempsRestant */
    private $servicePlatauTempsRestant;

    public function __construct(
        EntityManagerInterface $entityManager,
        DossierRepository $dossierRepository,
        Security $security,
        PlatauTempsRestant $servicePlatauTempsRestant
    ) {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
        $this->security = $security;
        $this->servicePlatauTempsRestant = $servicePlatauTempsRestant;
    }

    /**
     * Rétablit un dossier supprimé en remettant à null les champs de suppression.
     */
    public function restore(DossierEntity $dossier): void
    {
        $dossier->setDateSuppression(null);
        $dossier->setDeletedBy(null);

        $this->entityManager->flush();
    }

    public function nombreNouvellesPiecesJointes(DossierEntity $dossier): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof Utilisateur) {
            return 0;
        }

        $dateDerniereVisitePage = $user->getDerniereVisitePiecesDossier();

        if (null === $dateDerniereVisitePage) {
            return 0;
        }

        return $this->dossierRepository->findNombreNouvellesPieces($dossier, $dateDerniereVisitePage);
    }

    public function extraireDonneesUtiles(array $dossiers): array
    {
        $donneesUtiles = [];
        foreach ($dossiers as $dossier) {
            $donneeUtile = [];
            /** @var DossierEntity $dossierEntity */
            $dossierEntity = $dossier[0];
            $donneeUtile['nbDossiersLies'] = count($dossierEntity->getDossiersLies());
            if ($donneeUtile['nbDossiersLies'] > 0) {
                $donneeUtile['alerteReceptionTravaux'] = $this->dossierRepository->hasAlerteReceptionTravaux($dossierEntity);
                $donneeUtile['echeancierTravaux'] = $this->dossierRepository->hasEcheancierTravaux($dossierEntity);
            } else {
                $donneeUtile['alerteReceptionTravaux'] = false;
                $donneeUtile['echeancierTravaux'] = false;
            }

            $donneeUtile['verrou'] = $dossierEntity->hasVerrou();
            $donneeUtile['id'] = $dossierEntity->getId();
            $donneeUtile['nbPJ'] = count($dossierEntity->getPiecesJointes());
            $donneeUtile['objet'] = $dossierEntity->getObjet();
            $donneeUtile['incomplet'] = $dossierEntity->isIncomplet();
            $donneeUtile['dateReception'] = $dossierEntity->getDateReception();
            $donneeUtile['dateReponse'] = $dossierEntity->getDateReponse();
            $donneeUtile['typeId'] = $dossierEntity->getType()->getId();
            $donneeUtile['avisDiffere'] = $dossierEntity->hasAvisDiffere();
            $donneeUtile['avisId'] = $dossierEntity->getAvis() ? $dossierEntity->getAvis()->getId() : null;
            $donneeUtile['donneAvis'] = $dossierEntity->donneAvis();

            if (null !== $donneeUtile['objet']) {
                if (strlen($donneeUtile['objet']) > 70) {
                    $donneeUtile['objet'] = substr($donneeUtile['objet'], 0, 70).'(...)';
                }

                if (count($dossierEntity->getDocumentsUrbanisme()) > 0) {
                    $donneeUtile['objet'] .= ' - '.count($dossierEntity->getDocumentsUrbanisme());
                }
            } elseif (count($dossierEntity->getDocumentsUrbanisme()) > 0) {
                $donneeUtile['objet'] = count($dossierEntity->getDocumentsUrbanisme());
            } else {
                $donneeUtile['objet'] = '';
            }

            $libelle = '';
            if (!empty($dossier['type'])) {
                $libelle .= $dossier['type'];
            }

            if (!empty($dossier['nature'])) {
                if (!empty($libelle)) {
                    $libelle .= ' - ';
                }

                $libelle .= $dossier['nature'];
            }

            if (!empty($donneeUtile['objet'])) {
                if (!empty($libelle)) {
                    $libelle .= ' - ';
                }

                $libelle .= $donneeUtile['objet'];
            }

            $donneeUtile['objet'] = $libelle;
            $donneeUtile['etablissement'] = $dossier['etablissement'];

            if (null != $dossierEntity->getDateCommission()) {
                $donneeUtile['date'] = $dossierEntity->getDateCommission();
                $donneeUtile['libelleDate'] = 'ayant pour date de passage en commission le';
            } elseif (null != $dossierEntity->getDateVisite()) {
                $donneeUtile['date'] = $dossierEntity->getDateVisite();
                $donneeUtile['libelleDate'] = 'ayant pour date de visite le';
            } else {
                $donneeUtile['date'] = $dossierEntity->getDateInsert();
                $donneeUtile['libelleDate'] = 'créé le';
            }

            $consultationPlatau = $dossierEntity->getConsultationPlatau();
            if (null != $consultationPlatau && null != $consultationPlatau->getDateReponseAttendue()) {
                $donneeUtile['consultationPlatauId'] = $consultationPlatau->getIdentifiant();
                $donneeUtile['tempsRestant'] = $this->servicePlatauTempsRestant->calculate($consultationPlatau->getDateReponseAttendue());
                $donneeUtile['couleur'] = $this->servicePlatauTempsRestant->getCouleurTempsRestant($consultationPlatau->getDateReponseAttendue());
            } else {
                $donneeUtile['consultationPlatauId'] = null;
                $donneeUtile['tempsRestant'] = null;
                $donneeUtile['couleur'] = null;
            }

            $donneeUtile['isNew'] = $this->isNew($dossierEntity);
            $donneeUtile['hasNewPJ'] = $this->nombreNouvellesPiecesJointes($dossierEntity) > 0;

            $donneesUtiles[] = $donneeUtile;
        }

        return $donneesUtiles;
    }

    public function isNew(DossierEntity $dossier): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        $dateDerniereVisitePage = $user->getDerniereVisitePiecesDossier();

        if (null === $dateDerniereVisitePage) {
            return true;
        }

        return null != $dossier->getDateNotification() && $dossier->getDateNotification() >= $dateDerniereVisitePage;
    }
}
