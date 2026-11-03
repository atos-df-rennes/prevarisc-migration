<?php

namespace App\Controller;

use App\Entity\Periodicite;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\EtablissementTypeRepository;
use App\Repository\IghClasseRepository;
use App\Repository\PeriodiciteRepository;
use App\ValueObject\ClesTableauDesPeriodicites;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[IsGranted('periodicites')]
final class TableauDesPeriodicitesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PeriodiciteRepository $periodiciteRepository, private readonly EtablissementTypeRepository $etablissementTypeRepository, private readonly EtablissementCategorieRepository $etablissementCategorieRepository, private readonly IghClasseRepository $ighClasseRepository, private readonly TagAwareCacheInterface $searchCache)
    {
    }

    #[Route(path: '/admin/tableau-des-periodicites', name: 'admin_tableau_des_periodicites')]
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

    #[Route(path: '/admin/tableau-des-periodicites/sauvegarder', name: 'admin_tableau_des_periodicites_save')]
    public function save(Request $request): RedirectResponse
    {
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

            if ($periodicite < 0 || $periodicite > 999) {
                throw new \InvalidArgumentException('La périodicité doit être comprise entre 0 et 999 mois inclus.');
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

            $this->entityManager->persist($periodiciteEntity);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Le tableau des périodicités a bien été sauvegardé.');

        return $this->redirectToRoute('admin_tableau_des_periodicites');
    }

    #[Route(path: '/admin/tableau-des-periodicites/appliquer', name: 'admin_tableau_des_periodicites_apply', methods: ['POST'])]
    public function apply(Request $request): RedirectResponse
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

            $this->searchCache->invalidateTags(['search']);

            return $this->redirectToRoute('admin_tableau_des_periodicites');
        } catch (\Throwable $throwable) {
            $this->addFlash('danger', "Erreur lors de l'application des périodicités : ".$throwable->getMessage());
        }

        return $this->redirectToRoute('admin_tableau_des_periodicites');
    }
}
