<?php

namespace App\Repository;

use App\Entity\PrescriptionDossierAssociation;
use App\Entity\PrescriptionReglementaireAssociation;
use App\Entity\PrescriptionTexteListe;
use App\Entity\PrescriptionTypeAssociation;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionTexteListe::class);
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
     * Replaces references to an entity with a new entity across various associations and removes the old entity.
     *
     * Updates the references to the specified `PrescriptionTexteListe` entity across `PrescriptionDossierAssociation`,
     * `PrescriptionTypeAssociation`, and `PrescriptionReglementaireAssociation` while replacing them with a new entity.
     * Finally, removes the old entity from the database and optionally flushes changes to the database.
     *
     * @param PrescriptionTexteListe $entity    the entity to be replaced and removed
     * @param PrescriptionTexteListe $newEntity the entity that will replace the old entity in associations
     * @param bool                   $flush     whether to flush the changes to the database after execution
     */
    public function replaceAndRemove(PrescriptionTexteListe $entity, PrescriptionTexteListe $newEntity, bool $flush = true): void
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();
        $qb->update(PrescriptionDossierAssociation::class, 'pda')
            ->set('pda.texte', ':newEntity')
            ->where('pda.texte = :oldEntity')
            ->setParameter('newEntity', $newEntity)
            ->setParameter('oldEntity', $entity)
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->update(PrescriptionTypeAssociation::class, 'pta')
            ->set('pta.texte', ':newEntity')
            ->where('pta.texte = :oldEntity')
            ->setParameter('newEntity', $newEntity)
            ->setParameter('oldEntity', $entity)
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->update(PrescriptionReglementaireAssociation::class, 'pra')
            ->set('pra.texte', ':newEntity')
            ->where('pra.texte = :oldEntity')
            ->setParameter('newEntity', $newEntity)
            ->setParameter('oldEntity', $entity)
            ->getQuery()
            ->execute();

        $em->remove($entity);

        if ($flush) {
            $em->flush();
        }
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
}
