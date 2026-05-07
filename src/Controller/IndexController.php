<?php

namespace App\Controller;

use App\Builder\VersionBuilder;
use App\Entity\Utilisateur;
use App\Repository\GroupeRepository;
use App\Service\Dashboard\DashboardBlocConfig;
use App\Service\Dashboard\DashboardService;
use App\Service\FeedService;
use App\Service\NotificationService;
use App\Service\PlatauHealthcheckService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class IndexController extends AbstractController
{
    /** @var VersionBuilder */
    private $versionBuilder;

    /** @var DashboardBlocConfig */
    private $dashboardBlocConfig;

    /** @var DashboardService */
    private $dashboardService;

    /** @var FeedService */
    private $feedService;

    /** @var GroupeRepository */
    private $groupeRepository;

    /** @var NotificationService */
    private $notificationService;

    /** @var PlatauHealthcheckService */
    private $platauHealthcheckService;

    /** @var bool */
    private $deactivatePlatau;

    /** @var bool */
    private $platauNoHealthcheck;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        VersionBuilder $versionBuilder,
        DashboardBlocConfig $dashboardBlocConfig,
        DashboardService $dashboardService,
        FeedService $feedService,
        GroupeRepository $groupeRepository,
        NotificationService $notificationService,
        PlatauHealthcheckService $platauHealthcheckService,
        bool $deactivatePlatau,
        bool $platauNoHealthcheck,
        LoggerInterface $logger
    ) {
        $this->versionBuilder = $versionBuilder;
        $this->dashboardBlocConfig = $dashboardBlocConfig;
        $this->dashboardService = $dashboardService;
        $this->feedService = $feedService;
        $this->groupeRepository = $groupeRepository;
        $this->notificationService = $notificationService;
        $this->platauHealthcheckService = $platauHealthcheckService;
        $this->deactivatePlatau = $deactivatePlatau;
        $this->platauNoHealthcheck = $platauNoHealthcheck;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function homepage(): Response
    {
        return $this->redirectToRoute('accueil_index', [], 301);
    }

    /**
     * @Route("/accueil", name="accueil_index", methods={"GET"})
     */
    public function index(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $blocsConfig = $this->dashboardBlocConfig->getBlocConfig();
        $blocs = [];

        foreach ($blocsConfig as $blocId => $blocConfig) {
            if (null === $blocConfig['acl'] || $this->isGranted($blocConfig['acl'])) {
                try {
                    $count = $this->dashboardService->getCountForBloc($blocId, $user);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors du chargement du compteur du bloc dashboard "{bloc}" : {message}', [
                        'bloc' => $blocId,
                        'message' => $e->getMessage(),
                        'exception' => $e,
                    ]);
                    $count = 0;
                }

                $blocs[$blocId] = [
                    'type' => $blocConfig['type'],
                    'title' => $blocConfig['title'],
                    'count' => $count,
                ];
            }
        }

        // Détermination de l'ordre des blocs (port direct de indexAction legacy)
        $preferences = $user->getPreferences();
        $blocsDashboard = $preferences ? $preferences->getBlocsDashboard() : null;

        $blocsOrder = array_keys($blocsConfig);
        if ($blocsDashboard) {
            $decoded = json_decode($blocsDashboard, true);
            if (is_array($decoded)) {
                $blocsOrder = $decoded;
                // Traitement du cas où un nouveau bloc est ajouté
                foreach (array_keys($blocsConfig) as $defaultBloc) {
                    if (!in_array($defaultBloc, $blocsOrder, true)) {
                        $blocsOrder[] = $defaultBloc;
                    }
                }
            }
        }

        return $this->render('index/index.html.twig', [
            'user' => $user,
            'blocs' => $blocs,
            'blocsOrder' => $blocsOrder,
            'deactivatePlatau' => $this->deactivatePlatau,
            'platauNoHealthcheck' => $this->platauNoHealthcheck,
        ]);
    }

    /**
     * Chargement AJAX d'un bloc du tableau de bord.
     *
     * @Route("/accueil/bloc", name="accueil_bloc", methods={"POST"})
     */
    public function bloc(Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $rawId = $request->request->get('id', '');
        $id = is_string($rawId) ? $rawId : '';
        $blocsConfig = $this->dashboardBlocConfig->getBlocConfig();
        $bloc = [];

        if (isset($blocsConfig[$id])) {
            $blocConfig = $blocsConfig[$id];

            if (null === $blocConfig['acl'] || $this->isGranted($blocConfig['acl'])) {
                $bloc = [
                    'id' => $id,
                    'data' => $this->dashboardService->getDataForBloc($id, $user),
                    'type' => $blocConfig['type'],
                    'title' => $blocConfig['title'],
                ];
            }
        }

        if ('dossierPlatau' === $id) {
            $this->notificationService->setLastVisitDate($user, NotificationService::DASHBOARD_NAMESPACE);
        }

        return $this->render('index/bloc.html.twig', [
            'bloc' => $bloc,
        ]);
    }

    /**
     * Formulaire d'ajout d'un message dans le flux.
     *
     * @Route("/accueil/add-message", name="accueil_add_message", methods={"GET", "POST"})
     */
    public function addMessage(Request $request): Response
    {
        $groupes = $this->groupeRepository->findAll();

        if ($request->isMethod('POST')) {
            $rawToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('add_message', is_string($rawToken) ? $rawToken : null)) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            try {
                /** @var Utilisateur $user */
                $user = $this->getUser();
                $rawType = $request->request->get('type', '');
                $type = is_string($rawType) ? $rawType : '';
                $rawText = $request->request->get('text', '');
                $text = is_string($rawText) ? $rawText : '';
                $conf = $request->request->get('conf', []);

                if (!is_array($conf)) {
                    $conf = [];
                }

                /** @var int[] $confIds */
                $confIds = array_map(static function ($v): int { return is_numeric($v) ? (int) $v : 0; }, $conf);

                $this->feedService->addMessage($type, $text, $user, $confIds);
                $this->addFlash('success', 'Le message a bien été ajouté.');
            } catch (\Exception $e) {
                $this->addFlash('danger', "Erreur lors de l'ajout du message : ".$e->getMessage());
            }

            return $this->redirectToRoute('accueil_index');
        }

        return $this->render('index/add_message.html.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * Suppression d'un message du flux.
     *
     * @Route("/accueil/delete-message", name="accueil_delete_message", methods={"POST"})
     */
    public function deleteMessage(Request $request): Response
    {
        $rawToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_message', is_string($rawToken) ? $rawToken : null)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        try {
            $id = filter_var($request->request->get('id'), FILTER_VALIDATE_INT);
            if (false !== $id) {
                $news = $this->feedService->findMessage((int) $id);
                if (null !== $news) {
                    /** @var Utilisateur $currentUser */
                    $currentUser = $this->getUser();
                    if (null !== $news->getUtilisateur() && $news->getUtilisateur()->getId() !== $currentUser->getId()) {
                        throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer le message d'un autre utilisateur.");
                    }

                    $this->feedService->deleteMessage((int) $id);
                }
            }

            $this->addFlash('success', 'Le message a bien été supprimé.');
        } catch (AccessDeniedException $e) {
            throw $e;
        } catch (\Exception $exception) {
            $this->addFlash('danger', 'Erreur lors de la suppression du message : '.$exception->getMessage());
        }

        return $this->redirectToRoute('accueil_index');
    }

    /**
     * Vérification de la santé de Plat'AU (AJAX).
     *
     * @Route("/accueil/platau-healthcheck", name="accueil_platau_healthcheck", methods={"GET"}, options={"expose"=true})
     */
    public function platauHealthcheck(): JsonResponse
    {
        return new JsonResponse($this->platauHealthcheckService->executeHealthcheck());
    }

    /**
     * Rendu du footer (cached).
     */
    public function footer(): Response
    {
        $versionService = $this->versionBuilder->build();
        $response = $this->render('_footer.html.twig', [
            'link' => $versionService->link(),
            'version' => $versionService->version(),
        ]);
        $response->setPublic();
        $response->setMaxAge(604800);

        return $response;
    }
}
