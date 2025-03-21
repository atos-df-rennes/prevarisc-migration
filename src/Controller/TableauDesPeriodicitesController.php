<?php

namespace App\Controller;

use App\Entity\Periodicite;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\EtablissementTypeRepository;
use App\Repository\IghClasseRepository;
use App\Repository\PeriodiciteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TableauDesPeriodicitesController extends AbstractController
{
    /**
     * @Route("/admin/tableau-des-periodicites", name="admin_tableau_des_periodicites")
     */
    public function index(
        PeriodiciteRepository $periodiciteRepository,
        EtablissementTypeRepository $etablissementTypeRepository,
        EtablissementCategorieRepository $etablissementCategorieRepository,
        IghClasseRepository $ighClasseRepository
    ): Response
    {
        $periodicites = $periodiciteRepository->findAll();
        $types = $etablissementTypeRepository->findAll();
        $categories = $etablissementCategorieRepository->findAll();
        $classes = $ighClasseRepository->findAll();
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
    public function save(Request $request, PeriodiciteRepository $periodiciteRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($request->request->all() as $periodiciteKey => $periodicite) {
            [$categorie, $type, $localSommeil] = explode('_', $periodiciteKey);

            $periodiciteEntity = $periodiciteRepository->findOneBy([
                'categorie' => $categorie,
                'type' => $type,
                'localSommeil' => $localSommeil
            ]);

            if (null !== $periodiciteEntity) {
                if ((int) $periodicite === $periodiciteEntity->getPeriodicite()) {
                    continue;
                }

                $periodiciteEntity->setPeriodicite($periodicite);
            } else {
                $periodiciteEntity = new Periodicite();

                $periodiciteEntity->setCategorie($categorie);
                $periodiciteEntity->setType($type);
                $periodiciteEntity->setLocalSommeil($localSommeil);
                $periodiciteEntity->setPeriodicite($periodicite);
            }

            $entityManager->persist($periodiciteEntity);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le tableau des périodicités a bien été sauvegardé.');

        return $this->redirectToRoute('admin_tableau_des_periodicites');
    }
}
