<?php

namespace App\Repository;

use App\Entity\PrescriptionTexteListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionTexteListe>
 *
 * @method PrescriptionTexteListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionTexteListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionTexteListe[]    findAll()
 * @method PrescriptionTexteListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionTexteListeRepository extends ServiceEntityRepository
{
    /** @var PrescriptionDossierAssociationRepository */
    private $prescriptionDossierAssociationRepository;

    /** @var PrescriptionTypeAssociationRepository */
    private $prescriptionTypeAssociationRepository;

    /** @var PrescriptionReglementaireAssociationRepository */
    private $prescriptionReglementaireAssociationRepository;

    public function __construct(
        ManagerRegistry $registry,
        PrescriptionDossierAssociationRepository $prescriptionDossierAssociationRepository,
        PrescriptionTypeAssociationRepository $prescriptionTypeAssociationRepository,
        PrescriptionReglementaireAssociationRepository $prescriptionReglementaireAssociationRepository
    ) {
        parent::__construct($registry, PrescriptionTexteListe::class);
        $this->prescriptionDossierAssociationRepository = $prescriptionDossierAssociationRepository;
        $this->prescriptionTypeAssociationRepository = $prescriptionTypeAssociationRepository;
        $this->prescriptionReglementaireAssociationRepository = $prescriptionReglementaireAssociationRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionTexteListe $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(PrescriptionTexteListe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remplace les références de l'entité spécifiée par celles d'une nouvelle entité, puis supprime l'entité originale.
     *
     * Cette méthode encapsule l'opération dans une transaction pour garantir l'intégrité des données.
     *
     * @param PrescriptionTexteListe $entity    L'entité à remplacer et à supprimer
     * @param PrescriptionTexteListe $newEntity la nouvelle entité remplaçant l'ancienne
     */
    public function replaceAndRemove(PrescriptionTexteListe $entity, PrescriptionTexteListe $newEntity): void
    {
        $em = $this->getEntityManager();
        $em->wrapInTransaction(function ($em) use ($entity, $newEntity): void {
            $this->prescriptionDossierAssociationRepository->replaceAllTexts($entity, $newEntity);
            $this->prescriptionTypeAssociationRepository->replaceAllTexts($entity, $newEntity);
            $this->prescriptionReglementaireAssociationRepository->replaceAllTexts($entity, $newEntity);

            $em->remove($entity);
        });
    }

    /**
     * Récupère tous les textes, avec une option pour filtrer par visibilité.
     *
     * @return PrescriptionTexteListe[]
     */
    public function findAllTextes(?bool $visible = null): array
    {
        $qb = $this->createQueryBuilder('ptl')
            ->orderBy('ptl.libelle', 'ASC');

        if (null !== $visible) {
            $qb->where('ptl.visible = :visible')
                ->setParameter('visible', $visible);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère une liste de choix basée sur les objets PrescriptionTexteListe, triée par libellé.
     *
     * Cette méthode permet de filtrer les résultats en fonction de leur visibilité.
     *
     * @param bool|null $visible optionnel, filtre les éléments visibles uniquement si une valeur est fournie (true/false)
     *
     * @return PrescriptionTexteListe[] tableau associatif où la clé correspond au libellé et la valeur à l'identifiant
     */
    public function findAllForChoices(?bool $visible = null): array
    {
        $items = $this->findAllTextes($visible);

        $result = [];
        foreach ($items as $item) {
            $result[$item->getLibelle()] = $item->getId();
        }

        return $result;
    }
}
