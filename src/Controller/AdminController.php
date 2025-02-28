<?php

namespace App\Controller;

use App\Builder\VersionBuilder;
use App\Dto\Ldap;
use App\Repository\UtilisateurRepository;
use App\Service\Informations;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin", name="admin_")
 * @IsGranted("admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(UtilisateurRepository $utilisateurRepository, VersionBuilder $versionBuilder): Response
    {
        $versionService = $versionBuilder->build();

        return $this->render('admin/index.html.twig', [
            'version' => $versionService->version(),
            'is_up_to_date' => $versionService->isUpToDate(),
            'controller_name' => 'AdminController',
            'db_name' => $this->getParameter('app.db_name'),
            'db_url' => Informations::dbUrl($this->getParameter('app.db_host'), $this->getParameter('app.db_port')),
            'files_path' => $this->getParameter('app.files_path'),
            'cache_enabled' => $this->getParameter('app.cache_lifetime') > 0,
            'cache_adapter' => $this->getParameter('app.cache_adapter'),
            'cache_url' => Informations::cacheUrl($this->getParameter('app.cache_host'), $this->getParameter('app.cache_port')),
            'cache_lifetime' => $this->getParameter('app.cache_lifetime'),
            'ign_keys' => Informations::ignKeys($this->getParameter('app.ign_key')),
            'google_map_key' => $this->getParameter('app.google_map_key'),
            'geoconcept_infos' => [
                'URL' => $this->getParameter('app.geoconcept_url'),
                'Layer' => $this->getParameter('app.geoconcept_layer'),
                'App ID' => $this->getParameter('app.geoconcept_app_id'),
                'Projection' => $this->getParameter('app.geoconcept_projection'),
                'Token' => $this->getParameter('app.geoconcept_token'),
                'Geocoder URL' => $this->getParameter('app.geoconcept_geocoder'),
            ],
            'api_enabled' => '' !== $this->getParameter('app.security_key'),
            'proxy_enabled' => $this->getParameter('app.proxy_enabled'),
            'third_party_plugins' => Informations::thirdPartyPlugins($this->getParameter('app.third_party_plugins')),
            'authentification' => Informations::authentification(
                $this->getParameter('app.cas_enabled'),
                $this->getParameter('app.ntlm_enabled'),
                new Ldap(
                    $this->getParameter('app.ldap_enabled'),
                    $this->getParameter('app.ldap_host'),
                    $this->getParameter('app.ldap_port'),
                    $this->getParameter('app.ldap_basedn')
                )
            ),
            'enforce_security' => $this->getParameter('app.enforce_security'),
            'relance_periodicite_commission' => $this->getParameter('app.relance_periodicite_commission'),
            'periodicite_annees' => $this->getParameter('app.periodicite_annees'),
            'deactivate_platau' => $this->getParameter('app.deactivate_platau'),
            'nb_dossiers_a_afficher' => $this->getParameter('app.nb_dossiers_a_afficher'),
            'utilisateurs' => $utilisateurRepository->findConnected(),
        ]);
    }
}
