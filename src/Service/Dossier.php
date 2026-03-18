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

    /**
     * Extrait les données utiles pour l'affichage des dossiers.
     *
     * @param array<DossierEntity> $dossiers Tableau de dossiers (chaque élément est un tableau associatif)
     *
     * @return array<int, array<string, mixed>> Tableau des données utiles pour chaque dossier
     */
    public function extraireDonneesUtiles(array $dossiers): array
    {
        if ([] === $dossiers) {
            return [];
        }

        // Collecte de tous les IDs une seule fois
        $allIds = [];
        foreach ($dossiers as $dossier) {
            $id = $dossier->getId();
            if (null !== $id) {
                $allIds[] = $id;
            }
        }

        // Pré-calcul des IDs avec enfants (dl déjà chargé via addSelect dans le repository)
        $idsAvecEnfants = [];
        foreach ($dossiers as $dossier) {
            if (count($dossier->getDossiersLies()) > 0) {
                $id = $dossier->getId();
                if (null !== $id) {
                    $idsAvecEnfants[] = $id;
                }
            }
        }

        // Bulk : alertes, PJ count et nouvelles PJ — 4 requêtes max hors boucle
        $alertes = $this->dossierRepository->hasAlerteReceptionTravauxBulk($idsAvecEnfants);
        $echeanciers = $this->dossierRepository->hasEcheancierTravauxBulk($idsAvecEnfants);
        $nbPJParDossier = $this->dossierRepository->countPiecesJointesBulk($allIds);

        $user = $this->security->getUser();
        $hasNewPJParDossier = [];
        if ($user instanceof Utilisateur) {
            $dateDerniereVisite = $user->getDerniereVisitePiecesDossier();
            if (null !== $dateDerniereVisite) {
                $hasNewPJParDossier = $this->dossierRepository->hasNouvellesPiecesBulk($allIds, $dateDerniereVisite);
            }
        }

        // Pré-calcul isNew : dépend uniquement de champs scalaires déjà hydratés
        $dateDerniereVisiteDossier = ($user instanceof Utilisateur)
            ? $user->getDerniereVisitePiecesDossier()
            : null;

        $donneesUtiles = [];
        foreach ($dossiers as $dossierEntity) {
            $donneeUtile = [];
            $donneeUtile['nbDossiersLies'] = count($dossierEntity->getDossiersLies());
            $id = $dossierEntity->getId();
            if ($donneeUtile['nbDossiersLies'] > 0) {
                $donneeUtile['alerteReceptionTravaux'] = $alertes[$id] ?? false;
                $donneeUtile['echeancierTravaux'] = $echeanciers[$id] ?? false;
            } else {
                $donneeUtile['alerteReceptionTravaux'] = false;
                $donneeUtile['echeancierTravaux'] = false;
            }

            $donneeUtile['verrou'] = $dossierEntity->hasVerrou();
            $donneeUtile['id'] = $id;
            $donneeUtile['nbPJ'] = $nbPJParDossier[$id] ?? 0;
            $donneeUtile['objet'] = $dossierEntity->getObjet();
            $donneeUtile['incomplet'] = $dossierEntity->isIncomplet();
            $donneeUtile['dateReception'] = $dossierEntity->getDateReception();
            $donneeUtile['dateReponse'] = $dossierEntity->getDateReponse();
            $donneeUtile['avisDiffere'] = $dossierEntity->hasAvisDiffere();
            $donneeUtile['avisId'] = $dossierEntity->getAvisCommission() ? $dossierEntity->getAvisCommission()->getId() : null;
            $donneeUtile['donneAvis'] = $dossierEntity->donneAvis();
            $donneeUtile['dateReceptionFormatee'] = null !== $dossierEntity->getDateReception()
                ? $this->formatDateDiff($dossierEntity->getDateReception())
                : null;

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
            $type = $dossierEntity->getType();
            if (!is_null($type)) {
                if (!is_null($type->getLibelle())) {
                    $libelle .= $type->getLibelle();
                }

                $donneeUtile['typeId'] = $type->getId();
            } else {
                $donneeUtile['typeId'] = null;
            }

            $nature = !is_null($dossierEntity->getNature()) ? $dossierEntity->getNature()->getLibelle() : null;
            if (null !== $nature) {
                if ('' !== $libelle) {
                    $libelle .= ' - ';
                }

                $libelle .= $nature;
            }

            if ('' !== $donneeUtile['objet']) {
                if ('' !== $libelle) {
                    $libelle .= ' - ';
                }

                $libelle .= $donneeUtile['objet'];
            }

            $donneeUtile['objet'] = $libelle;
            if ($dossierEntity->getEtablissements()->first() && $dossierEntity->getEtablissements()->first()->getInformations()->first()) {
                $donneeUtile['etablissement'] = $dossierEntity->getEtablissements()->first()->getInformations()->first()->getLibelle();
            } else {
                $donneeUtile['etablissement'] = null;
            }

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

            $donneeUtile['isNew'] = $this->isNewFromCache($dossierEntity, $dateDerniereVisiteDossier);
            $donneeUtile['hasNewPJ'] = $hasNewPJParDossier[$id] ?? false;

            $donneesUtiles[] = $donneeUtile;
        }

        return $donneesUtiles;
    }

    /**
     * Indique si un dossier est considéré comme nouveau pour l'utilisateur courant.
     */
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

    /**
     * Version optimisée de isNew() utilisant la date déjà récupérée hors boucle.
     */
    private function isNewFromCache(DossierEntity $dossier, ?\DateTimeInterface $dateDerniereVisite): bool
    {
        if (!$this->security->getUser() instanceof Utilisateur) {
            return false;
        }

        if (null === $dateDerniereVisite) {
            return true;
        }

        return null != $dossier->getDateNotification() && $dossier->getDateNotification() >= $dateDerniereVisite;
    }

    /**
     * Formate la différence entre une date et maintenant (ex: "2 jours", "1 heure").
     *
     * Reproduit le comportement du helper Zend View_Helper_Dates::formatDateDiff().
     */
    private function formatDateDiff(\DateTimeInterface $start): string
    {
        $end = new \DateTime();
        $interval = $end->diff($start);

        $doPlural = function (int $nb, string $str): string {
            return $nb > 1 ? $str.'s' : $str;
        };

        $format = [];
        if (0 !== $interval->y) {
            $format[] = '%y '.$doPlural($interval->y, 'année');
        }

        if (0 !== $interval->m) {
            $format[] = '%m mois';
        }

        if (0 !== $interval->d) {
            $format[] = '%d '.$doPlural($interval->d, 'jour');
        }

        if (0 !== $interval->h) {
            $format[] = '%h '.$doPlural($interval->h, 'heure');
        }

        if (0 !== $interval->i) {
            $format[] = '%i '.$doPlural($interval->i, 'minute');
        }

        if (0 !== $interval->s) {
            if ([] === $format) {
                return '<= 1 min';
            }

            $format[] = '%s '.$doPlural($interval->s, 'seconde');
        }

        $pattern = array_shift($format);

        if (null === $pattern) {
            return '<= 1 min';
        }

        return $interval->format($pattern);
    }
}
