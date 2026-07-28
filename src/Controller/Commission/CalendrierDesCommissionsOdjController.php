<?php

namespace App\Controller\Commission;

use App\Dto\Repository\DossierAffectationDto;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Utils\Date;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gestion de l'ordre du jour (ODJ) d'une date de commission.
 * Extrait de CalendrierDesCommissionsController (REF-CTL-01).
 */
class CalendrierDesCommissionsOdjController extends AbstractController
{
    public function __construct(private readonly DateCommissionRepository $dateCommissionRepository, private readonly DossierAffectationRepository $dossierAffectationRepository, private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Page principale de gestion de l'ordre du jour (ODJ).
     * Port direct de gestionodjAction().
     */
    #[Route(path: '/commissions/calendrier/gestion-odj', name: 'calendrier_des_commissions_gestion_odj', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('modification_odj')]
    public function gestionOdj(Request $request): Response
    {
        /** @var string $rawDateCommId */
        $rawDateCommId = $request->query->get('dateCommId', '0');
        $dateCommId = (int) $rawDateCommId;

        $dateCommission = $this->dateCommissionRepository->find($dateCommId);
        if (null === $dateCommission) {
            throw $this->createNotFoundException(sprintf('Date de commission #%d introuvable', $dateCommId));
        }

        // Détermination de la commission principale (port direct : null → id courant)
        if (null === $dateCommission->getDateCommissionLiees()) {
            $commPrincipale = $dateCommId;
        } else {
            $commPrincipale = (int) $dateCommission->getDateCommissionLiees();
        }

        // Dates liées (liste pour tableau navigation)
        $recupCommLiees = $this->dateCommissionRepository->getCommissionsQtypListing($commPrincipale);
        // Pré-formatage de la dateFr pour chaque date liée
        foreach ($recupCommLiees as $idx => $commLiee) {
            /** @var array<string, mixed> $commLieeTyped */
            $commLieeTyped = $commLiee;
            $rawDate = $commLieeTyped['DATE_COMMISSION'];
            $dateStr = is_string($rawDate) ? $rawDate : '';
            $dateLieeObj = \DateTime::createFromFormat('Y-m-d', $dateStr);
            $recupCommLiees[$idx]['dateFr'] = false !== $dateLieeObj ? Date::formatDateFr($dateLieeObj) : $dateStr;
        }

        $commission = $dateCommission->getCommission();
        $commissionType = null !== $commission ? $commission->getType() : null;

        // Dossiers affectés (avec horaire) et non affectés (sans horaire)
        $listeDossiersNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($dateCommId);
        $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($dateCommId);
        $nbDossiers = count($listeDossiersAffect) + count($listeDossiersNonAffect);

        // Enrichissement des dossiers non affectés avec infosEtab, listeDocUrba, preventionnistes
        $listeDossiersNonAffect = $this->enrichDossiers($listeDossiersNonAffect);

        // Formatage de la date principale en français
        $date = $dateCommission->getDate();
        $dateFr = null !== $date ? Date::formatDateFr($date) : '';

        return $this->render('calendrier_des_commissions/gestionodj.html.twig', [
            'dateCommission' => $dateCommission,
            'commission' => $commission,
            'commissionType' => $commissionType,
            'dateFr' => $dateFr,
            'listeDossierNonAffect' => $listeDossiersNonAffect,
            'nbDossiersAffect' => count($listeDossiersAffect),
            'nbDossiers' => $nbDossiers,
            'recupCommLiees' => $recupCommLiees,
        ]);
    }

    /**
     * Retourne les événements FullCalendar JSON pour les dossiers affectés à une date de commission (ODJ).
     * Port direct de recupevenementodjAction().
     */
    #[Route(path: '/commissions/calendrier/recup-evenement-odj', name: 'calendrier_des_commissions_recup_evenement_odj', methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('modification_odj')]
    public function recupEvenementOdj(Request $request): JsonResponse
    {
        /** @var string $rawStart */
        $rawStart = $request->query->get('start', '0');
        /** @var string $rawEnd */
        $rawEnd = $request->query->get('end', '0');
        /** @var string $rawDateCommId */
        $rawDateCommId = $request->query->get('dateCommId', '0');
        $dateCommId = (int) $rawDateCommId;

        // Conversion ms → sec → date 'Y-m-d' (port de Zend_Date::TIMESTAMP + format YEAR-MONTH-DAY)
        $dateDebut = date('Y-m-d', (int) substr($rawStart, 0, -3));
        $dateFin = date('Y-m-d', (int) substr($rawEnd, 0, -3));

        $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($dateCommId);

        $conn = $this->entityManager->getConnection();

        // 1er passage : enrichissement établissement + doc urba ; unset si aucun établissement (port direct)
        foreach ($listeDossiersAffect as $val => $ue) {
            $idDossier = (int) $ue->ID_DOSSIER;

            /** @var array<int, array<string, mixed>> $listeEtab */
            $listeEtab = $conn->fetchAllAssociative(
                'SELECT ed.ID_ETABLISSEMENT FROM etablissementdossier ed
                 JOIN etablissement e ON ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                 WHERE ed.ID_DOSSIER = :id AND e.DATESUPPRESSION_ETABLISSEMENT IS NULL LIMIT 1',
                ['id' => $idDossier]
            );

            if ([] !== $listeEtab) {
                $idEtab = is_numeric($listeEtab[0]['ID_ETABLISSEMENT']) ? (int) $listeEtab[0]['ID_ETABLISSEMENT'] : 0;
                $listeDossiersAffect[$val]->infosEtab = $this->loadEtablissementInfos($idEtab);
                $listeDossiersAffect[$val]->listeDocUrba = $conn->fetchAllAssociative(
                    'SELECT NUM_DOCURBA FROM dossierdocurba WHERE ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
            } else {
                unset($listeDossiersAffect[$val]);
            }
        }

        // 2e passage : construction des événements FullCalendar
        $color = null;
        $items = [];
        foreach ($listeDossiersAffect as $dossierAffect) {
            /** @var array<string, mixed> $infosEtab */
            $infosEtab = null !== $dossierAffect->infosEtab ? (array) $dossierAffect->infosEtab : [];
            /** @var array<string, mixed> $informations */
            $informations = isset($infosEtab['informations']) ? (array) $infosEtab['informations'] : [];
            /** @var array<int, array<string, mixed>> $adresses */
            $adresses = isset($infosEtab['adresses']) ? (array) $infosEtab['adresses'] : [];
            /** @var array<int, array<string, mixed>> $parents */
            $parents = isset($infosEtab['parents']) ? (array) $infosEtab['parents'] : [];

            // Extraction typée des champs scalaires
            $idDossier = (int) $dossierAffect->ID_DOSSIER;
            $libelleDossierNature = is_string($dossierAffect->LIBELLE_DOSSIERNATURE) ? $dossierAffect->LIBELLE_DOSSIERNATURE : '';
            $objetDossier = is_string($dossierAffect->OBJET_DOSSIER) ? $dossierAffect->OBJET_DOSSIER : '';
            $heureDebAffect = is_string($dossierAffect->HEURE_DEB_AFFECT) ? $dossierAffect->HEURE_DEB_AFFECT : '';
            $heureFinAffect = is_string($dossierAffect->HEURE_FIN_AFFECT) ? $dossierAffect->HEURE_FIN_AFFECT : '';
            $libelleEtab = isset($informations['LIBELLE_ETABLISSEMENTINFORMATIONS']) && is_string($informations['LIBELLE_ETABLISSEMENTINFORMATIONS'])
                ? $informations['LIBELLE_ETABLISSEMENTINFORMATIONS'] : '';

            $affichage = '';
            if (isset($parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS']) && is_string($parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                $affichage .= $parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'].' - ';
            }

            $affichage .= $libelleEtab;

            $nbAdresse = count($adresses);
            if (0 !== $nbAdresse) {
                $affichage .= ' (';
                foreach ($adresses as $commune) {
                    $affichage .= is_string($commune['LIBELLE_COMMUNE']) ? $commune['LIBELLE_COMMUNE'] : '';
                    if (1 !== $nbAdresse) {
                        $affichage .= ', ';
                    }

                    --$nbAdresse;
                }

                $affichage .= ') ';
            } else {
                $affichage .= ' ( adresse non renseignée )';
            }

            if ('' !== $libelleDossierNature) {
                $affichage .= ' - '.$libelleDossierNature;
            }

            if ('' !== $objetDossier) {
                $affichage .= ' - Objet : '.$objetDossier;
            }

            /** @var array<int, array<string, mixed>> $listeDocUrba */
            $listeDocUrba = null !== $dossierAffect->listeDocUrba ? (array) $dossierAffect->listeDocUrba : [];
            if (count($listeDocUrba) > 0) {
                $affichage .= ' - Doc urbanisme : ';
                foreach ($listeDocUrba as $docUrba) {
                    $affichage .= (is_string($docUrba['NUM_DOCURBA']) ? $docUrba['NUM_DOCURBA'] : '').' . ';
                }
            }

            /** @var array<int, array<string, mixed>> $preventionnistes */
            $preventionnistes = $conn->fetchAllAssociative(
                'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS
                 FROM dossierpreventionniste dp
                 JOIN utilisateur u ON dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR
                 JOIN utilisateurinformations ui ON u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                 WHERE dp.ID_DOSSIER = :id',
                ['id' => $idDossier]
            );

            if ([] !== $preventionnistes) {
                $nom0 = is_string($preventionnistes[0]['NOM_UTILISATEURINFORMATIONS']) ? $preventionnistes[0]['NOM_UTILISATEURINFORMATIONS'] : '';
                $prenom0 = is_string($preventionnistes[0]['PRENOM_UTILISATEURINFORMATIONS']) ? $preventionnistes[0]['PRENOM_UTILISATEURINFORMATIONS'] : '';
                $affichage .= ' ('.$nom0.' '.$prenom0.')';
            }

            if (0 == $dossierAffect->VERROU_DOSSIER) {
                $color = '#e2a420';
            } elseif (1 == $dossierAffect->VERROU_DOSSIER) {
                $color = '#83bff6';
            }

            $items[] = [
                'id' => $idDossier,
                'url' => $this->generateUrl('dossier_index', ['id' => $idDossier]),
                'title' => '   '.$affichage,
                'start' => $dateDebut.' '.$heureDebAffect,
                'end' => $dateFin.' '.$heureFinAffect,
                'backgroundColor' => $color,
                'allDay' => false,
            ];
        }

        return $this->json(['items' => $items]);
    }

    /**
     * Affecte un dossier à un créneau horaire (drop depuis le panneau latéral vers le calendrier).
     * Port direct de affectedossodjAction().
     */
    #[Route(path: '/commissions/calendrier/affecte-doss-odj', name: 'calendrier_des_commissions_affecte_doss_odj', methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('modification_odj')]
    public function affecteOdj(Request $request): JsonResponse
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $rawIdDossier */
            $rawIdDossier = $request->request->get('idDossier', '0');
            /** @var string $datadebut */
            $datadebut = $request->request->get('datadebut', '');

            $dateCommId = (int) $rawDateCommId;
            $idDossier = (int) $rawIdDossier;

            $dossAffect = $this->dossierAffectationRepository->findOneBy([
                'dateCommission' => $dateCommId,
                'dossier' => $idDossier,
            ]);

            if (null === $dossAffect) {
                throw new \RuntimeException(sprintf('Affectation introuvable : dateComm=%d dossier=%d', $dateCommId, $idDossier));
            }

            $dateAttribDoss = new \DateTime($datadebut);
            $dossAffect->setHeureDebut($dateAttribDoss);

            $dateAttribDossPlus5 = clone $dateAttribDoss;
            $dateAttribDossPlus5->modify('+5 minutes');
            $dossAffect->setHeureFin($dateAttribDossPlus5);

            $this->entityManager->persist($dossAffect);
            $this->entityManager->flush();

            $dossierEntity = $dossAffect->getDossier();
            $verrou = null !== $dossierEntity ? (int) $dossierEntity->hasVerrou() : 0;

            $listeDossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($dateCommId);

            return $this->json([
                'verrou' => $verrou,
                'countAffect' => count($listeDossiersAffect),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('danger', "Erreur lors de l'affectation du dossier : ".$exception->getMessage());

            return $this->json(['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Redimensionne un événement ODJ (mise à jour de l'heure de fin uniquement).
     * Port direct de resizeodjAction().
     */
    #[Route(path: '/commissions/calendrier/resize-odj', name: 'calendrier_des_commissions_resize_odj', methods: ['POST'])]
    #[IsGranted('lecture_commission')]
    public function resizeOdj(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $rawIdDossier */
            $rawIdDossier = $request->request->get('idDossier', '0');
            /** @var string $dateFin */
            $dateFin = $request->request->get('dateFin', '');

            $dateCommId = (int) $rawDateCommId;
            $idDossier = (int) $rawIdDossier;

            $heureFin = new \DateTime($dateFin);

            $dossierAffectation = $this->dossierAffectationRepository->findOneBy([
                'dateCommission' => $dateCommId,
                'dossier' => $idDossier,
            ]);

            if (null === $dossierAffectation) {
                throw new \RuntimeException(sprintf('Affectation introuvable : dateComm=%d dossier=%d', $dateCommId, $idDossier));
            }

            $dossierAffectation->setHeureFin($heureFin);
            $this->entityManager->persist($dossierAffectation);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => "L'événement a bien été modifié."]);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => "Erreur lors de la modification de l'événement : ".$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Déplace un événement ODJ (mise à jour heure de début et de fin).
     * Port direct de dropodjAction().
     */
    #[Route(path: '/commissions/calendrier/drop-odj', name: 'calendrier_des_commissions_drop_odj', methods: ['POST'])]
    #[IsGranted('modification_odj')]
    public function dropOdj(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $rawIdDossier */
            $rawIdDossier = $request->request->get('idDossier', '0');
            /** @var string $heureDebutParam */
            $heureDebutParam = $request->request->get('heureDebut', '');
            /** @var string $heureFinParam */
            $heureFinParam = $request->request->get('heureFin', '');

            $dateCommId = (int) $rawDateCommId;
            $idDossier = (int) $rawIdDossier;

            $heureDeb = new \DateTime($heureDebutParam);
            $heureFin = new \DateTime($heureFinParam);

            $dossierAffectation = $this->dossierAffectationRepository->findOneBy([
                'dateCommission' => $dateCommId,
                'dossier' => $idDossier,
            ]);

            if (null === $dossierAffectation) {
                throw new \RuntimeException(sprintf('Affectation introuvable : dateComm=%d dossier=%d', $dateCommId, $idDossier));
            }

            $dossierAffectation->setHeureDebut($heureDeb);
            $dossierAffectation->setHeureFin($heureFin);
            $this->entityManager->persist($dossierAffectation);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Événement déplacé avec succès.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur inattendue : '.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bascule entre mode horaire (GESTION_HEURES=1) et séquentiel (GESTION_HEURES=0).
     * Port direct de gestionheuresAction().
     */
    #[Route(path: '/commissions/calendrier/gestion-heures', name: 'calendrier_des_commissions_gestion_heures', methods: ['POST'])]
    #[IsGranted('modification_odj')]
    public function gestionHeures(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $gestionHeureParam */
            $gestionHeureParam = $request->request->get('gestionHeure', '');

            $dateCommId = (int) $rawDateCommId;

            $gestionHeure = null;
            if ('non' === $gestionHeureParam) {
                $gestionHeure = 0;
            } elseif ('oui' === $gestionHeureParam) {
                $gestionHeure = 1;
            }

            $dateCommission = $this->dateCommissionRepository->find($dateCommId);
            if (null === $dateCommission) {
                throw new \RuntimeException(sprintf('Date de commission #%d introuvable', $dateCommId));
            }

            $dateCommission->setGestionHeures(1 === $gestionHeure);
            $this->entityManager->persist($dateCommission);
            $this->entityManager->flush();

            $listeDossiersConcernes = $this->dossierAffectationRepository->findAllForCommission($dateCommId);

            if (0 === $gestionHeure) {
                // Mode séquentiel : reset heures, numérotation 0,1,2,...
                $nbDossier = 0;
                foreach ($listeDossiersConcernes as $val) {
                    /** @var array<string, mixed> $valTyped */
                    $valTyped = $val;
                    $idDossierAffect = is_numeric($valTyped['ID_DOSSIER_AFFECT']) ? (int) $valTyped['ID_DOSSIER_AFFECT'] : 0;
                    if (0 !== $idDossierAffect) {
                        $dossierAffect = $this->dossierAffectationRepository->findOneBy([
                            'dateCommission' => $dateCommId,
                            'dossier' => $idDossierAffect,
                        ]);
                        if (null !== $dossierAffect) {
                            $dossierAffect->setHeureDebut(null);
                            $dossierAffect->setHeureFin(null);
                            $dossierAffect->setNumero($nbDossier);
                            $this->entityManager->persist($dossierAffect);
                        }
                    }

                    ++$nbDossier;
                }
            } else {
                // Mode horaire : reset heures, NUM_DOSSIER = 0 pour tous
                foreach ($listeDossiersConcernes as $val) {
                    /** @var array<string, mixed> $valTyped */
                    $valTyped = $val;
                    $idDossierAffect = is_numeric($valTyped['ID_DOSSIER_AFFECT']) ? (int) $valTyped['ID_DOSSIER_AFFECT'] : 0;
                    $dossierAffect = $this->dossierAffectationRepository->findOneBy([
                        'dateCommission' => $dateCommId,
                        'dossier' => $idDossierAffect,
                    ]);
                    if (null !== $dossierAffect) {
                        $dossierAffect->setHeureDebut(null);
                        $dossierAffect->setHeureFin(null);
                        $dossierAffect->setNumero(0);
                        $this->entityManager->persist($dossierAffect);
                    }
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', "La modification de l'événement a bien été enregistrée");
        } catch (\Exception $exception) {
            $this->addFlash('danger', "Erreur lors de la modification de l'événement : ".$exception->getMessage());
        }

        return new Response('');
    }

    /**
     * Réordonnancement séquentiel des dossiers par drag & drop.
     * Port direct de changementordreAction().
     */
    #[Route(path: '/commissions/calendrier/changement-ordre', name: 'calendrier_des_commissions_changement_ordre', methods: ['POST'])]
    #[IsGranted('modification_odj')]
    public function changementOrdre(Request $request): Response
    {
        try {
            /** @var string $rawDateCommId */
            $rawDateCommId = $request->request->get('dateCommId', '0');
            /** @var string $stringUpdate */
            $stringUpdate = $request->request->get('ordreDossier', '');

            $dateCommId = (int) $rawDateCommId;
            $dossierId = explode(',', $stringUpdate);

            $numDossier = 0;
            foreach ($dossierId as $idDossier) {
                $updateOrdreDossier = $this->dossierAffectationRepository->findOneBy([
                    'dateCommission' => $dateCommId,
                    'dossier' => (int) $idDossier,
                ]);
                if (null !== $updateOrdreDossier) {
                    $updateOrdreDossier->setHeureDebut(null);
                    $updateOrdreDossier->setHeureFin(null);
                    $updateOrdreDossier->setNumero($numDossier);
                    $this->entityManager->persist($updateOrdreDossier);
                }

                ++$numDossier;
            }

            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Ordre des dossiers mis à jour.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur inattendue : '.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Enrichit un tableau de dossiers bruts avec infosEtab, listeDocUrba et preventionnistes.
     * Port du bloc d'enrichissement inline de gestionodjAction().
     *
     * @param DossierAffectationDto[] $dossiers
     *
     * @return DossierAffectationDto[]
     */
    private function enrichDossiers(array $dossiers): array
    {
        $conn = $this->entityManager->getConnection();

        foreach ($dossiers as $val => $ue) {
            $idDossier = (int) $ue->ID_DOSSIER;

            /** @var array<int, array<string, mixed>> $listeEtab */
            $listeEtab = $conn->fetchAllAssociative(
                'SELECT ed.ID_ETABLISSEMENT FROM etablissementdossier ed
                 JOIN etablissement e ON ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                 WHERE ed.ID_DOSSIER = :id AND e.DATESUPPRESSION_ETABLISSEMENT IS NULL LIMIT 1',
                ['id' => $idDossier]
            );

            if ([] !== $listeEtab) {
                $idEtab = is_numeric($listeEtab[0]['ID_ETABLISSEMENT']) ? (int) $listeEtab[0]['ID_ETABLISSEMENT'] : 0;
                $dossiers[$val]->infosEtab = $this->loadEtablissementInfos($idEtab);
                $dossiers[$val]->listeDocUrba = $conn->fetchAllAssociative(
                    'SELECT NUM_DOCURBA FROM dossierdocurba WHERE ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
                $dossiers[$val]->preventionnistes = $conn->fetchAllAssociative(
                    'SELECT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS
                     FROM dossierpreventionniste dp
                     JOIN utilisateur u ON dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR
                     JOIN utilisateurinformations ui ON u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                     WHERE dp.ID_DOSSIER = :id',
                    ['id' => $idDossier]
                );
            }
        }

        return $dossiers;
    }

    /**
     * Charge les informations d'affichage d'un établissement (informations, adresses, parents).
     * Équivalent partiel de Service_Etablissement::get() pour les besoins ODJ.
     *
     * @return array<string, mixed>
     */
    private function loadEtablissementInfos(int $idEtab): array
    {
        $conn = $this->entityManager->getConnection();

        // Libellé principal (dernière fiche d'informations)
        $informations = $conn->fetchAssociative(
            'SELECT ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementinformations ei
             WHERE ei.ID_ETABLISSEMENT = :id
             ORDER BY ei.DATE_ETABLISSEMENTINFORMATIONS DESC
             LIMIT 1',
            ['id' => $idEtab]
        );

        // Adresses (communes)
        /** @var array<int, array<string, mixed>> $adresses */
        $adresses = $conn->fetchAllAssociative(
            'SELECT c.LIBELLE_COMMUNE
             FROM etablissementadresse ea
             JOIN adressecommune c ON ea.NUMINSEE_COMMUNE = c.NUMINSEE_COMMUNE
             WHERE ea.ID_ETABLISSEMENT = :id',
            ['id' => $idEtab]
        );

        // Parents directs (table etablissementlie : ID_FILS_ETABLISSEMENT = enfant, ID_ETABLISSEMENT = parent)
        /** @var array<int, array<string, mixed>> $parents */
        $parents = $conn->fetchAllAssociative(
            'SELECT ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementlie el
             JOIN etablissementinformations ei ON el.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                 AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                     SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                     FROM etablissementinformations ei2
                     WHERE ei2.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                 )
             WHERE el.ID_FILS_ETABLISSEMENT = :id',
            ['id' => $idEtab]
        );

        return [
            'informations' => false !== $informations ? $informations : ['LIBELLE_ETABLISSEMENTINFORMATIONS' => ''],
            'adresses' => $adresses,
            'parents' => $parents,
        ];
    }
}
