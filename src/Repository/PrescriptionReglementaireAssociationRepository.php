<?php

namespace App\Repository;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionReglementaireAssociation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionReglementaireAssociation>
 *
 * @method PrescriptionReglementaireAssociation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionReglementaireAssociation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionReglementaireAssociation[]    findAll()
 * @method PrescriptionReglementaireAssociation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionReglementaireAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionReglementaireAssociation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionReglementaireAssociation $entity, bool $flush = true): void
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
    public function remove(PrescriptionReglementaireAssociation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remplace toutes les occurrences d'un ancien article par un nouveau article dans les associations réglementaires.
     *
     * @param PrescriptionArticleListe $oldArticle L'ancien article à remplacer.
     * @param PrescriptionArticleListe $newArticle Le nouvel article qui remplace l'ancien.
     *
     * @return void
     */
    public function replaceAllArticles(PrescriptionArticleListe $oldArticle, PrescriptionArticleListe $newArticle): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(PrescriptionReglementaireAssociation::class, 'pra')
            ->set('pra.article', ':newArticle')
            ->where('pra.article = :oldArticle')
            ->setParameter('newArticle', $newArticle)
            ->setParameter('oldArticle', $oldArticle)
            ->getQuery()
            ->execute()
        ;
    }
}
