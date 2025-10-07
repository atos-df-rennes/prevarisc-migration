<?php

namespace App\Repository;

use App\Entity\PrescriptionArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionArticle>
 *
 * @method PrescriptionArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionArticle[]    findAll()
 * @method PrescriptionArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionArticle::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionArticle $entity, bool $flush = true): void
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
    public function remove(PrescriptionArticle $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère le numéro maximal.
     *
     * @return int le numéro maximal
     */
    public function recupMaxNumCat(): int
    {
        $qb = $this->createQueryBuilder('article');
        $qb->select('max(article.numero)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findByArticleWithContenu(PrescriptionArticle $article): array
    {
        $qb = $this->createQueryBuilder('article')
            ->addSelect('texte')
            ->leftJoin('association.texte', 'texte')
            ->leftJoin('association.article', 'article')
            ->where('association.prescriptionType = :idPrescription')
            ->setParameter('idPrescription', $prescriptionId)
            ->orderBy('association.numero');

        return $qb->getQuery()->getResult();
    }
}
