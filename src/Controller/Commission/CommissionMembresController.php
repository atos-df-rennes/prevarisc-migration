<?php

namespace App\Controller\Commission;

use App\Entity\Commission;
use App\Entity\DossierNatureListe;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementType;
use App\Form\CommissionMembresCollectionType;
use App\Repository\CommissionMembreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_commissions")
 */
class CommissionMembresController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommissionMembreRepository */
    private $membreRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommissionMembreRepository $membreRepository
    ) {
        $this->entityManager = $entityManager;
        $this->membreRepository = $membreRepository;
    }

    /**
     * Onglet Membres — gestion des membres d'une commission.
     *
     * @Route("/admin/commissions/{id}/membres", name="admin_commissions_membres", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function membres(Commission $commission, Request $request): Response
    {
        // Pré-chargement des membres avec leurs relations pour éviter N+1
        $this->membreRepository->findByCommissionWithRelations($commission);

        // Activités ERP groupées par type (pour le FormType et le template)
        $activitesByType = $this->buildActivitesByType();

        $form = $this->createForm(CommissionMembresCollectionType::class, $commission, [
            'commission' => $commission,
            'activites_by_type' => $activitesByType,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Les membres ont été sauvegardés avec succès.');

            return $this->redirectToRoute('admin_commissions_membres', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'membres',
            'form' => $form->createView(),
            'activites_by_type' => $activitesByType,
            'natures_by_type' => $this->buildNaturesByType(),
        ]);
    }

    /**
     * Charge toutes les activités ERP groupées par libellé de type.
     *
     * @return array<string, EtablissementActivite[]>
     */
    private function buildActivitesByType(): array
    {
        /** @var EtablissementType[] $types */
        $types = $this->entityManager->getRepository(EtablissementType::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.activites', 'a')
            ->addSelect('a')
            ->orderBy('t.libelle', 'ASC')
            ->addOrderBy('a.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        $activitesByType = [];
        foreach ($types as $type) {
            /** @var EtablissementActivite[] $activites */
            $activites = $type->getActivites()->toArray();
            if ([] !== $activites) {
                $activitesByType[(string) $type->getLibelle()] = $activites;
            }
        }

        return $activitesByType;
    }

    /**
     * Charge toutes les natures de dossier groupées par ID de type de dossier.
     *
     * @return array<int, DossierNatureListe[]>
     */
    private function buildNaturesByType(): array
    {
        /** @var DossierNatureListe[] $natures */
        $natures = $this->entityManager->getRepository(DossierNatureListe::class)
            ->createQueryBuilder('n')
            ->leftJoin('n.type', 'dt')
            ->addSelect('dt')
            ->orderBy('dt.libelle', 'ASC')
            ->addOrderBy('n.ordre', 'ASC')
            ->addOrderBy('n.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        $naturesGrouped = [];
        foreach ($natures as $nature) {
            $dossierType = $nature->getType();
            if (null === $dossierType) {
                continue;
            }

            $typeId = (int) $dossierType->getId();
            if (!isset($naturesGrouped[$typeId])) {
                $naturesGrouped[$typeId] = [];
            }

            $naturesGrouped[$typeId][] = $nature;
        }

        return $naturesGrouped;
    }
}
