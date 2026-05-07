<?php

namespace App\Controller\Commission;

use App\Repository\CommissionRepository;
use App\Repository\DateCommissionRepository;
use Sabre\VObject\Component\VCalendar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Export ICS (Outlook) des dates de commission.
 * Extrait de CalendrierDesCommissionsController (REF-CTL-01).
 */
class CalendrierDesCommissionsExportController extends AbstractController
{
    /**
     * @var DateCommissionRepository
     */
    private $dateCommissionRepository;

    /**
     * @var CommissionRepository
     */
    private $commissionRepository;

    public function __construct(
        DateCommissionRepository $dateCommissionRepository,
        CommissionRepository $commissionRepository
    ) {
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->commissionRepository = $commissionRepository;
    }

    /**
     * Génère un fichier ICS pour une DateCommission unique (export Outlook).
     * Port direct de exportoutlookAction() — bug ligne 1539 ($ics .= $idDateComm) non reproduit.
     *
     * @Route("/commissions/calendrier/export-outlook/{dateCommId}", name="calendrier_des_commissions_export_outlook", methods={"GET"}, requirements={"dateCommId"="\d+"})
     */
    public function exportOutlook(int $dateCommId): Response
    {
        $dateCommission = $this->dateCommissionRepository->find($dateCommId);

        if (null === $dateCommission) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $date = $dateCommission->getDate();
        $heureDebut = $dateCommission->getHeureDebut();
        $heureFin = $dateCommission->getHeureFin();

        if (in_array(null, [$date, $heureDebut, $heureFin], true)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $tz = new \DateTimeZone('Europe/Paris');
        $dtStart = new \DateTime($date->format('Y-m-d').' '.$heureDebut->format('H:i:s'), $tz);
        $dtEnd = new \DateTime($date->format('Y-m-d').' '.$heureFin->format('H:i:s'), $tz);

        $libelle = $dateCommission->getLibelle() ?? '';
        $description = $libelle;
        $commission = $dateCommission->getCommission();
        if (null !== $commission) {
            $description .= ' / Commission : '.$commission->getLibelle();
        }

        $calendar = new VCalendar(['METHOD' => 'REQUEST', 'PRODID' => 'SDIS62/Prevarisc']);
        $calendar->add('VEVENT', [
            'SUMMARY' => $libelle,
            'DESCRIPTION' => $description,
            'DTSTART' => $dtStart,
            'DTEND' => $dtEnd,
            'ORGANIZER' => 'mailto:prevarisc@atos.net',
            'UID' => sprintf('prevarisc-datecomm-%d@prevarisc.local', $dateCommId),
            'SEQUENCE' => 0,
            'DTSTAMP' => new \DateTime('now', new \DateTimeZone('UTC')),
        ]);

        $response = new Response($calendar->serialize());
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        /** @var string $disposition */
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'calendar.ics');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Génère un fichier ICS mensuel pour une commission (export Outlook — mois complet).
     * Port direct de exportoutlookmoisAction().
     *
     * @Route("/commissions/calendrier/export-outlook-mois/{commId}/{mois}/{annee}", name="calendrier_des_commissions_export_outlook_mois", methods={"GET"}, requirements={"commId"="\d+", "mois"="\d+", "annee"="\d+"}, options={"expose"=true})
     */
    public function exportOutlookMois(int $commId, int $mois, int $annee): Response
    {
        if ($mois < 1 || $mois > 12 || $annee < 1) {
            return new Response('Paramètres invalides.', Response::HTTP_BAD_REQUEST);
        }

        $commission = $this->commissionRepository->find($commId);
        $commissions = $this->dateCommissionRepository->findByMonthAndCommission($mois, $annee, $commId);

        if (null === $commission) {
            return new Response('Commission introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ([] === $commissions) {
            return new Response('Aucune séance ce mois-ci.', Response::HTTP_NOT_FOUND);
        }

        $libelleCommission = $commission->getLibelle() ?? '';
        $tz = new \DateTimeZone('Europe/Paris');
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        $calendar = new VCalendar(['PRODID' => 'SDIS62/Prevarisc']);

        foreach ($commissions as $commissiondujour) {
            /** @var array<string, mixed> $commissiondujour */
            $dateCommStr = \is_string($commissiondujour['DATE_COMMISSION'] ?? null) ? $commissiondujour['DATE_COMMISSION'] : '';
            $heureDebStr = \is_string($commissiondujour['HEUREDEB_COMMISSION'] ?? null) ? $commissiondujour['HEUREDEB_COMMISSION'] : '00:00:00';
            $heureFinStr = \is_string($commissiondujour['HEUREFIN_COMMISSION'] ?? null) ? $commissiondujour['HEUREFIN_COMMISSION'] : '00:00:00';
            $libelleDate = \is_string($commissiondujour['LIBELLE_DATECOMMISSION'] ?? null) ? $commissiondujour['LIBELLE_DATECOMMISSION'] : '';
            $idDateComm = isset($commissiondujour['ID_DATECOMMISSION']) && is_numeric($commissiondujour['ID_DATECOMMISSION'])
                ? (int) $commissiondujour['ID_DATECOMMISSION']
                : 0;

            try {
                $dtStart = new \DateTime($dateCommStr.' '.$heureDebStr, $tz);
                $dtEnd = new \DateTime($dateCommStr.' '.$heureFinStr, $tz);
            } catch (\Exception $e) {
                continue;
            }

            $description = $libelleDate.' / Commission : '.$libelleCommission;

            $dossieraffecte = $this->dateCommissionRepository->findDossierAffectForCommission($idDateComm);
            if ([] !== $dossieraffecte) {
                $objetsDossiers = array_filter(array_map(function (array $d): string {
                    return \is_string($d['OBJET_DOSSIER'] ?? null) ? $d['OBJET_DOSSIER'] : '';
                }, $dossieraffecte));
                if ([] !== $objetsDossiers) {
                    $description .= ' / Ordre du jour : '.implode('; ', $objetsDossiers);
                }
            }

            $calendar->add('VEVENT', [
                'SUMMARY' => $libelleDate,
                'DESCRIPTION' => $description,
                'DTSTART' => $dtStart,
                'DTEND' => $dtEnd,
                'ORGANIZER' => 'mailto:prevarisc@atos.net',
                'UID' => sprintf('prevarisc-datecomm-%d@prevarisc.local', $idDateComm),
                'DTSTAMP' => $nowUtc,
            ]);
        }

        $slug = (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $libelleCommission);
        $filename = sprintf('calendar_%d_%d_%s.ics', $mois, $annee, $slug);

        $response = new Response($calendar->serialize());
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        /** @var string $disposition */
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
