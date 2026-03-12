<?php

namespace App\Controller;

use App\Entity\Periodicite;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\EtablissementTypeRepository;
use App\Repository\IghClasseRepository;
use App\Repository\PeriodiciteRepository;
use App\ValueObject\ClesTableauDesPeriodicites;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @IsGranted("periodicites")
 */
class TableauDesPeriodicitesController extends AbstractController
{
    /**
     * @var PeriodiciteRepository
     */
    private $periodiciteRepository;

    /**
     * @var EtablissementTypeRepository
     */
    private $etablissementTypeRepository;

    /**
     * @var EtablissementCategorieRepository
     */
    private $etablissementCategorieRepository;

    /**
     * @var IghClasseRepository
     */
    private $ighClasseRepository;

    /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        PeriodiciteRepository $periodiciteRepository,
        EtablissementTypeRepository $etablissementTypeRepository,
        EtablissementCategorieRepository $etablissementCategorieRepository,
        IghClasseRepository $ighClasseRepository,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->periodiciteRepository = $periodiciteRepository;
        $this->etablissementTypeRepository = $etablissementTypeRepository;
        $this->etablissementCategorieRepository = $etablissementCategorieRepository;
        $this->ighClasseRepository = $ighClasseRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/admin/tableau-des-periodicites", name="admin_tableau_des_periodicites")
     */
    public function index(): Response
    {
        $periodicites = $this->periodiciteRepository->findAll();
        $types = $this->etablissementTypeRepository->findAll();
        $categories = $this->etablissementCategorieRepository->findAll();
        $classes = $this->ighClasseRepository->findAll();
        $localSommeilTypes = $this->getParameter('app.local_sommeil_types');
        $result = [];
        foreach ($periodicites as $singlePeriodicite) {
            $result[$singlePeriodicite->getCategorie()][$singlePeriodicite->getType()][$singlePeriodicite->getLocalSommeil()] = $singlePeriodicite->getPeriodicite();
        }

        return $this->render('tableau_des_periodicites/index.html.twig', [
            'periodicites' => $periodicites,
            'types' => $types,
            'categories' => $categories,
            'classes' => $classes,
            'tableau' => $result,
            'local_sommeil_types' => explode(';', $localSommeilTypes),
        ]);
    }

    /**
     * @Route("/admin/tableau-des-periodicites/sauvegarder", name="admin_tableau_des_periodicites_save")
     */
    public function save(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($request->request->all() as $periodiciteKey => $periodicite) {
            $clesTableauDesPeriodicites = ClesTableauDesPeriodicites::fromRequest($periodiciteKey);

            $periodiciteEntity = $this->periodiciteRepository->findOneBy([
                'categorie' => $clesTableauDesPeriodicites->categorie(),
                'type' => $clesTableauDesPeriodicites->type(),
                'localSommeil' => $clesTableauDesPeriodicites->localSommeil(),
            ]);
            $periodicite = filter_var($periodicite, FILTER_VALIDATE_INT);

            if (false === $periodicite) {
                throw new \InvalidArgumentException("Le type de la périodicité n'est pas correct. Type attendu : entier.");
            }

            if (null !== $periodiciteEntity) {
                if ($periodicite === $periodiciteEntity->getPeriodicite()) {
                    continue;
                }
            } else {
                $periodiciteEntity = new Periodicite();

                $periodiciteEntity->setCategorie($clesTableauDesPeriodicites->categorie());
                $periodiciteEntity->setType($clesTableauDesPeriodicites->type());
                $periodiciteEntity->setLocalSommeil($clesTableauDesPeriodicites->localSommeil());
            }

            $periodiciteEntity->setPeriodicite($periodicite);

            $entityManager->persist($periodiciteEntity);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le tableau des périodicités a bien été sauvegardé.');

        return $this->redirectToRoute('admin_tableau_des_periodicites');
    }

    /**
     * @Route("/admin/tableau-des-periodicites/appliquer", name="admin_tableau_des_periodicites_apply", methods={"POST"})
     */
    public function apply(Request $request): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('apply_periodicites', $csrfToken)) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');

            return $this->redirectToRoute('admin_tableau_des_periodicites');
        }

        try {
            $this->periodiciteRepository->applyPeriodicites();
            $this->addFlash('success', 'Les périodicités ont bien été appliquées à tous les établissements.');

            $target = $this->urlGenerator->generate('admin_tableau_des_periodicites');

            return $this->redirect('/legacy/admin/clear-search-cache-and-redirect?target='.$target);
        } catch (\Throwable $throwable) {
            $this->addFlash('danger', 'Erreur lors de l\'application des périodicités : '.$throwable->getMessage());
        }

        return $this->redirectToRoute('admin_tableau_des_periodicites');
    }
}
