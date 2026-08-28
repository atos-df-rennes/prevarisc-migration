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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class IndexController extends AbstractController
{
    public function __construct(private readonly VersionBuilder $versionBuilder, private readonly DashboardBlocConfig $dashboardBlocConfig, private readonly DashboardService $dashboardService, private readonly FeedService $feedService, private readonly GroupeRepository $groupeRepository, private readonly NotificationService $notificationService, private readonly PlatauHealthcheckService $platauHealthcheckService, private readonly bool $deactivatePlatau, private readonly bool $platauNoHealthcheck, private readonly LoggerInterface $logger, private readonly bool $sdis29CustomEnabled)
    {
    }

    #[Route(path: '/', name: 'homepage')]
    public function homepage(): Response
    {
        return $this->redirectToRoute('accueil_index', [], 301);
    }

    #[Route(path: '/accueil', name: 'accueil_index', methods: ['GET'])]
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
     */
    #[Route(path: '/accueil/bloc', name: 'accueil_bloc', methods: ['POST'])]
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
     */
    #[Route(path: '/accueil/add-message', name: 'accueil_add_message', methods: ['GET', 'POST'])]
    public function addMessage(Request $request): Response
    {
        $this->denyAccessUnlessGranted('fil_actus');

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
                $requestData = $request->request->all();
                $conf = $requestData['conf'] ?? [];
                $conf = is_array($conf) ? $conf : [];

                /** @var int[] $confIds */
                $confIds = array_map(static fn ($v): int => is_numeric($v) ? (int) $v : 0, $conf);

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
     */
    #[Route(path: '/accueil/delete-message', name: 'accueil_delete_message', methods: ['POST'])]
    public function deleteMessage(Request $request): Response
    {
        $this->denyAccessUnlessGranted('fil_actus');

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
     */
    #[Route(path: '/accueil/platau-healthcheck', name: 'accueil_platau_healthcheck', methods: ['GET'], options: ['expose' => true])]
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
        $template = $this->sdis29CustomEnabled ? 'sdis29/_footer.html.twig' : '_footer.html.twig';
        $response = $this->render($template, [
            'link' => $versionService->link(),
            'version' => $versionService->version(),
        ]);
        $response->setPublic();
        $response->setMaxAge(604800);

        return $response;
    }
}
