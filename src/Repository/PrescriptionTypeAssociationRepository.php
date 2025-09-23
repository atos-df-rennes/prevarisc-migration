<?php

namespace App\Repository;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionTypeAssociation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionTypeAssociation>
 *
 * @method PrescriptionTypeAssociation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionTypeAssociation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionTypeAssociation[]    findAll()
 * @method PrescriptionTypeAssociation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionTypeAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionTypeAssociation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionTypeAssociation $entity, bool $flush = true): void
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
    public function remove(PrescriptionTypeAssociation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remplace tous les articles associés à un article donné par un nouvel article.
     *
     * @param PrescriptionArticleListe $oldArticle ancien article à remplacer
     * @param PrescriptionArticleListe $newArticle nouvel article qui remplacera l'ancien
     */
    public function replaceAllArticles(PrescriptionArticleListe $oldArticle, PrescriptionArticleListe $newArticle): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(PrescriptionTypeAssociation::class, 'pta')
            ->set('pta.article', ':newArticle')
            ->where('pta.article = :oldArticle')
            ->setParameter('newArticle', $newArticle)
            ->setParameter('oldArticle', $oldArticle)
            ->getQuery()
            ->execute()
        ;
    }
}
