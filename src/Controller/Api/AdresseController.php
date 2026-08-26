<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API d'autocomplétion des adresses.
 * Port de Api_AdresseController + Service_Adresse + Model_DbTable_EtablissementAdresse.
 */
class AdresseController extends AbstractController
{
    private const int LONGUEUR_CODE_POSTAL = 5;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Autocomplete des communes par nom ou code postal.
     */
    #[Route(path: '/api/2.0/adresse/get_communes', name: 'api_adresse_get_communes', methods: ['GET'])]
    public function getCommunes(Request $request): JsonResponse
    {
        $q = $this->extractString($request->query->get('q', ''));

        if ('' === $q) {
            return new JsonResponse(['response' => []]);
        }

        $limit = filter_var($request->query->get('limit', 50), FILTER_VALIDATE_INT);

        if (false === $limit) {
            $limit = 50;
        }

        $limit = max(1, min(50, $limit));

        if (self::LONGUEUR_CODE_POSTAL === strlen($q) && is_numeric($q)) {
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $this->connection->executeQuery(
                'SELECT * FROM adressecommune WHERE CODEPOSTAL_COMMUNE = ? LIMIT '.$limit,
                [$q]
            )->fetchAllAssociative();
        } else {
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $this->connection->executeQuery(
                'SELECT * FROM adressecommune WHERE LIBELLE_COMMUNE LIKE ? ORDER BY LENGTH(LIBELLE_COMMUNE) LIMIT '.$limit,
                ['%'.$q.'%']
            )->fetchAllAssociative();
        }

        return new JsonResponse(['response' => $rows]);
    }

    /**
     * Voies d'une commune pour autocomplete.
     * Accepte GET (autocomplete) et POST (dossier search via AJAX).
     */
    #[Route(path: '/api/2.0/adresse/get_voies', name: 'api_adresse_get_voies', methods: ['GET', 'POST'])]
    public function getVoies(Request $request): JsonResponse
    {
        $codeInsee = $this->extractString($request->get('code_insee', ''));
        $q = $this->extractString($request->get('q', ''));

        if ('' === $codeInsee) {
            return new JsonResponse(['response' => []]);
        }

        if ('' !== $q) {
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $this->connection->executeQuery(
                'SELECT * FROM adresserue WHERE NUMINSEE_COMMUNE = ? AND LIBELLE_RUE LIKE ?',
                [$codeInsee, '%'.$q.'%']
            )->fetchAllAssociative();
        } else {
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $this->connection->executeQuery(
                'SELECT * FROM adresserue WHERE NUMINSEE_COMMUNE = ?',
                [$codeInsee]
            )->fetchAllAssociative();
        }

        return new JsonResponse(['response' => $rows]);
    }

    /**
     * Numéros de rue pour une voie donnée.
     * Accepte GET et POST.
     */
    #[Route(path: '/api/2.0/adresse/get_numeros', name: 'api_adresse_get_numeros', methods: ['GET', 'POST'])]
    public function getNumeros(Request $request): JsonResponse
    {
        $idRue = $this->extractString($request->get('id_rue', ''));

        if ('' === $idRue) {
            return new JsonResponse(['response' => []]);
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery(
            'SELECT DISTINCT NUMERO_ADRESSE FROM etablissementadresse WHERE ID_RUE = ? ORDER BY NUMERO_ADRESSE',
            [(int) $idRue]
        )->fetchAllAssociative();

        return new JsonResponse(['response' => $rows]);
    }

    /**
     * Récupère le libellé d'une commune par code INSEE.
     * Port de Api_Service_Adresse::getLibelleCommune.
     */
    #[Route(path: '/api/2.0/adresse/get_libelle_commune', name: 'api_adresse_get_libelle_commune', methods: ['GET'])]
    public function getLibelleCommune(Request $request): JsonResponse
    {
        $codeInsee = $this->extractString($request->query->get('code_insee', ''));

        if ('' === $codeInsee) {
            return new JsonResponse(['response' => null]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $this->connection->executeQuery(
            'SELECT * FROM adressecommune WHERE NUMINSEE_COMMUNE = ?',
            [$codeInsee]
        )->fetchAssociative();

        if (false === $row) {
            return new JsonResponse(['response' => null]);
        }

        return new JsonResponse(['response' => $row]);
    }

    /**
     * Récupère le libellé d'une rue par son ID.
     * Port de Api_Service_Adresse::getLibelleRue.
     */
    #[Route(path: '/api/2.0/adresse/get_libelle_rue', name: 'api_adresse_get_libelle_rue', methods: ['GET'])]
    public function getLibelleRue(Request $request): JsonResponse
    {
        $idRue = $this->extractString($request->query->get('idRue', ''));

        if ('' === $idRue) {
            return new JsonResponse(['response' => null]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $this->connection->executeQuery(
            'SELECT * FROM adresserue WHERE ID_RUE = ?',
            [(int) $idRue]
        )->fetchAssociative();

        if (false === $row) {
            return new JsonResponse(['response' => null]);
        }

        return new JsonResponse(['response' => $row]);
    }

    /**
     * Extrait une valeur string depuis une valeur mixed de ParameterBag.
     *
     * @param mixed $value
     */
    private function extractString($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }
}
