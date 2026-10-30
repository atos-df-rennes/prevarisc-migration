<?php

declare(strict_types=1);

namespace App\Controller\Sdis29;

use App\Entity\EtablissementInformations;
use App\Entity\Etablissement;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Security\Voter\Sdis29\Sdis29Voter;
use App\Service\Sdis29\NumeroIdGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// @todo: faire un package chargé uniquement par le/les SDIS concernés
/**
 * Endpoints spécifiques à SDIS29, actifs uniquement quand
 * PREVARISC_THIRDPARTY_PLUGINS contient "Plugin_SDIS29" (cf. Sdis29Voter,
 * lié à app.sdis29_custom_enabled).
 *
 * Le même artefact applicatif étant déployé pour tous les SDIS, ce garde-fou
 * évite d'exposer ces routes chez les autres clients (404, comme si la route
 * n'existait pas).
 *
 * Portage de SDIS29_EtablissementController (legacy Zend, module "sdis29",
 * modules/sdis29/controllers/EtablissementController.php).
 */
class EtablissementController extends AbstractController
{
    public function __construct(private readonly NumeroIdGenerator $numeroIdGenerator)
    {
    }

    /**
     * Export KML (marqueur unique) d'un établissement, consommé par le
     * visualiseur Géobretagne (sviewer) en pièce jointe de carte.
     *
     * L'image de marqueur est servie depuis ce même serveur (public/images),
     * donc aucune clé de sécurité n'est nécessaire ici (contrairement au
     * legacy, qui exposait PREVARISC_SECURITY_KEY dans l'URL pour proxifier
     * l'image quand le viewer était sur un domaine tiers).
     */
    #[Route(path: '/sdis29/etablissement/{id}/kml', name: 'sdis29_etablissement_kml', methods: ['GET'])]
    #[IsGranted(Sdis29Voter::ENABLED, statusCode: 404)]
    #[IsGranted('view_ets', subject: 'etablissement')]
    public function kml(Etablissement $etablissement, Request $request): Response
    {
        $adresse = $etablissement->getAdresses()->first();
        if (false === $adresse || null === $adresse->getLongitude() || null === $adresse->getLatitude()) {
            throw new NotFoundHttpException("Cet établissement n'a pas d'adresse géolocalisée.");
        }

        $latestInformations = $etablissement->getLatestInformationsOrNull();

        $response = $this->render('sdis29/kml.xml.twig', [
            'libelle' => $latestInformations instanceof EtablissementInformations ? $latestInformations->getLibelle() : '',
            'adresse' => $adresse,
            'image_url' => $request->getSchemeAndHttpHost().'/images/red-dot.png',
        ]);

        $response->headers->set('Content-Type', 'application/vnd.google-earth.kml+xml');

        return $response;
    }

    /**
     * Génère une proposition de nouvel identifiant d'établissement, selon le
     * schéma de numérotation SDIS29 (utilisé uniquement depuis le formulaire
     * d'ajout d'un établissement : la valeur retournée est une suggestion,
     * l'unicité définitive reste garantie à la persistance).
     */
    #[Route(path: '/sdis29/etablissement/generateID', name: 'sdis29_etablissement_generate_id', methods: ['POST'])]
    #[IsGranted(Sdis29Voter::ENABLED, statusCode: 404)]
    #[IsGranted(EtablissementVoter::CREATE)]
    public function generateId(Request $request): JsonResponse
    {
        $arrondissement = $request->request->getString('arrondissement', '');
        $idGenre = $request->request->getString('idgenre', '1') ?: '1';
        $rawIdPere = $request->request->get('idpere');
        $idPere = (is_string($rawIdPere) || is_int($rawIdPere)) && '' !== (string) $rawIdPere ? (int) $rawIdPere : null;

        try {
            $identifiant = $this->numeroIdGenerator->generate($arrondissement, $idGenre, $idPere);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return new JsonResponse(['error' => $invalidArgumentException->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($identifiant);
    }
}
