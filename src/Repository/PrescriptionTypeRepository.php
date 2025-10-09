<?php

namespace App\Repository;

use App\Entity\PrescriptionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionType>
 *
 * @method PrescriptionType|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionType|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionType[]    findAll()
 * @method PrescriptionType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionType::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionType $entity, bool $flush = true): void
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
    public function remove(PrescriptionType $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Met à jour l'ordre en fonction d'un tableau fourni.
     *
     * @param array $order tableau contenant l'ordre des éléments (identifiants)
     */
    public function setOrder(array $order): void
    {
        $em = $this->getEntityManager();
        $elements = $this->findBy(['id' => $order]);

        foreach ($elements as $element) {
            $position = array_search($element->getId(), $order, true);
            $element->setNumero($position + 1);
        }

        $em->flush();
    }
}
