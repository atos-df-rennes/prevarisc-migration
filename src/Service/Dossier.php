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

    public function __construct(
        EntityManagerInterface $entityManager,
        DossierRepository $dossierRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
        $this->security = $security;
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
//            $donneeUtile['nature'] = $dossier['nature'];
//            $donneeUtile['type'] = $dossier['type'];
//            $donneeUtile['etablissement'] = $dossier['etablissement'];
            /** @var \App\Entity\Dossier $dossierEntity */
            $dossierEntity = $dossier[0];
            $donneeUtile['nbDossiersLies'] = count($dossierEntity->getDossiersLies());
            $donneeUtile['verrou'] = $dossierEntity->getVerrou();
            $donneeUtile['id'] = $dossierEntity->getId();
            $donneeUtile['nbPJ'] = count($dossierEntity->getPiecesJointes());
            $donneeUtile['objet'] = $dossierEntity->getObjet();
            if (null !== $donneeUtile['objet']) {
                if (strlen($donneeUtile['objet']) > 70) {
                    $donneeUtile['objet'] = substr($donneeUtile['objet'], 0, 70) . '(...)';
                }
                if (count($dossierEntity->getDocumentsUrbanisme()) > 0) {
                    $donneeUtile['objet'] .= ' - ' . count($dossierEntity->getDocumentsUrbanisme());
                }
            } else {
                if (count($dossierEntity->getDocumentsUrbanisme()) > 0) {
                    $donneeUtile['objet'] = count($dossierEntity->getDocumentsUrbanisme());
                } else {
                    $donneeUtile['objet'] = '';
                }
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

            if ($dossierEntity->getDateCommission() != null) {
                $donneeUtile['date'] = $dossierEntity->getDateCommission();
                $donneeUtile['libelleDate'] = "ayant pour date de passage en commission le";
            } elseif ($dossierEntity->getDateVisite() != null) {
                $donneeUtile['date'] = $dossierEntity->getDateVisite();
                $donneeUtile['libelleDate'] = "ayant pour date de visite le";
            } else {
                $donneeUtile['date'] = $dossierEntity->getDateInsert();
                $donneeUtile['libelleDate'] = "créé le";
            }

            // TODO : recuperer les infos du connecteur Platau et le service TempsRestant apres merge branche Maxime
            // is_new, has_new_pj
            $donneeUtile['tempsRestant'] = '';
            $donneeUtile['couleur'] = '';
            $donneeUtile['isNew'] = null;
            $donneeUtile['hasNewPJ'] = null;

            $donneesUtiles[] = $donneeUtile;
        }
        return $donneesUtiles;
    }
}
