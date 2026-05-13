<?php

namespace App\Controller\Api;

use App\Constant\GenreEtablissement;
use App\Repository\EtablissementRepository;
use App\Repository\PeriodiciteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API pour les établissements (autocomplete + valeurs par défaut).
 */
class EtablissementController extends AbstractController
{
    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var PeriodiciteRepository */
    private $periodiciteRepository;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        PeriodiciteRepository $periodiciteRepository
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->periodiciteRepository = $periodiciteRepository;
    }

    /**
     * Recherche d'établissements par libellé pour autocomplete.
     *
     * @Route("/api/etablissements/search", name="api_etablissements_search", methods={"GET"})
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (!is_string($query) || strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $limit = filter_var($request->query->get('limit', 20), FILTER_VALIDATE_INT);
        if (false === $limit) {
            $limit = 20;
        }

        $limit = max(1, min(50, $limit));

        $etablissements = $this->etablissementRepository->searchByLibelle($query, $limit);

        $results = [];
        foreach ($etablissements as $etab) {
            $info = $etab->getLatestInformations();
            $genre = null !== $info->getGenre() ? $info->getGenre()->getLibelle() : null;
            $results[] = [
                'id' => $etab->getId(),
                'libelle' => $info->getLibelle(),
                'genre' => $genre,
            ];
        }

        return new JsonResponse($results, Response::HTTP_OK);
    }

    /**
     * Retourne les valeurs conseillées (commission, périodicité, local à sommeil, préventionnistes)
     * pour un établissement selon son genre, catégorie, type et commune.
     *
     * Port de Api_Service_Etablissement::getDefaultValues() / Service_Etablissement::getDefaultValues().
     *
     * @Route("/api/1.0/etablissement/defaults_values", name="api_etablissement_defaults_values", methods={"GET","POST"})
     */
    public function defaultsValues(Request $request): JsonResponse
    {
        // Récupération des paramètres (GET ou POST)
        /** @var array<string, mixed> $params */
        $params = array_merge($request->query->all(), $request->request->all());

        $rawGenre = $params['genre'] ?? null;
        $genre = is_scalar($rawGenre) ? (int) $rawGenre : null;
        $rawNuminsee = $params['numinsee'] ?? '';
        $numinsee = is_scalar($rawNuminsee) && '' !== (string) $rawNuminsee ? (string) $rawNuminsee : null;
        $rawType = $params['type'] ?? '';
        $type = is_scalar($rawType) && '' !== (string) $rawType ? (int) $rawType : null;
        $rawCategorie = $params['categorie'] ?? '';
        $categorie = is_scalar($rawCategorie) && '' !== (string) $rawCategorie ? (int) $rawCategorie : null;
        $rawLocalSommeil = $params['local_sommeil'] ?? null;
        $localSommeil = null !== $rawLocalSommeil ? (bool) $rawLocalSommeil : null;
        $rawClasse = $params['classe'] ?? '';
        $classe = is_scalar($rawClasse) && '' !== (string) $rawClasse ? (int) $rawClasse : null;
        $rawIdPere = $params['id_etablissement_pere'] ?? '';
        $idEtablissementPere = is_scalar($rawIdPere) && '' !== (string) $rawIdPere
            ? (int) $rawIdPere
            : null;

        if (null === $genre) {
            return new JsonResponse(['error' => 'Le paramètre genre est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $results = $this->computeDefaultValues(
            $genre,
            $type,
            $categorie,
            $localSommeil,
            $classe,
            $idEtablissementPere
        );

        return new JsonResponse($results, Response::HTTP_OK);
    }

    /**
     * Calcule les valeurs conseillées selon le genre et les critères fournis.
     *
     * @param int       $genre               Identifiant du genre d'établissement
     * @param int|null  $type                Identifiant du type ERP
     * @param int|null  $categorie           Identifiant de la catégorie ERP
     * @param bool|null $localSommeil        Présence d'un local à sommeil
     * @param int|null  $classe              Identifiant de la classe IGH
     * @param int|null  $idEtablissementPere Identifiant de l'établissement père
     *
     * @return array<string, mixed>
     */
    private function computeDefaultValues(
        int $genre,
        ?int $type,
        ?int $categorie,
        ?bool $localSommeil,
        ?int $classe,
        ?int $idEtablissementPere
    ): array {
        $results = [];

        switch ($genre) {
            // Site
            case GenreEtablissement::SITE:
                // Cellule
            case GenreEtablissement::CELLULE:
                // Autres genres (Habitation, BUP, Camping, Manifestation temporaire, IOP, Zone)
            default:
                // Préventionnistes selon commune (portés côté client via autocomplete)
                $results['preventionnistes'] = [];

                break;
                // ERP
            case GenreEtablissement::ERP:
                // Suggestion local à sommeil : false par défaut si non renseigné
                if (null === $localSommeil) {
                    $results['local_sommeil'] = false;
                }

                // Suggestion local à sommeil selon type (types configurés dans PREVARISC_LOCAL_SOMMEIL_TYPES)
                if (null !== $type) {
                    /** @var string $localSommeilTypesParam */
                    $localSommeilTypesParam = $this->getParameter('app.local_sommeil_types');
                    $localSommeilTypes = array_map('intval', explode(';', $localSommeilTypesParam));

                    if (in_array($type, $localSommeilTypes, true)) {
                        $results['local_sommeil'] = true;
                    }
                }

                // Périodicité selon catégorie/type/local à sommeil
                if (!in_array(null, [$categorie, $type, $localSommeil], true)) {
                    $periodicite = $this->periodiciteRepository->gn4(
                        $categorie,
                        (string) $type,
                        $localSommeil
                    );
                    if (null !== $periodicite) {
                        $results['periodicite'] = $periodicite->getPeriodicite();
                    }
                }

                // Commission via établissement père si disponible
                if (null !== $idEtablissementPere) {
                    $pere = $this->etablissementRepository->find($idEtablissementPere);
                    if (null !== $pere) {
                        $commPere = $pere->getLatestInformations()->getCommission();
                        if (null !== $commPere) {
                            $results['commission'] = [
                                'ID_COMMISSION' => $commPere->getId(),
                                'LIBELLE_COMMISSION' => $commPere->getLibelle(),
                            ];
                        }
                    }
                }

                $results['preventionnistes'] = [];

                break;
                // IGH
            case GenreEtablissement::IGH:
                // Périodicité selon classe
                if (null !== $classe) {
                    $periodicite = $this->periodiciteRepository->gn4(0, (string) $classe, false);
                    if (null !== $periodicite) {
                        $results['periodicite'] = $periodicite->getPeriodicite();
                    }
                }

                // Commission via établissement père si disponible
                if (null !== $idEtablissementPere) {
                    $pere = $this->etablissementRepository->find($idEtablissementPere);
                    if (null !== $pere) {
                        $commPere = $pere->getLatestInformations()->getCommission();
                        if (null !== $commPere) {
                            $results['commission'] = [
                                'ID_COMMISSION' => $commPere->getId(),
                                'LIBELLE_COMMISSION' => $commPere->getLibelle(),
                            ];
                        }
                    }
                }

                $results['preventionnistes'] = [];

                break;
        }

        return $results;
    }
}
