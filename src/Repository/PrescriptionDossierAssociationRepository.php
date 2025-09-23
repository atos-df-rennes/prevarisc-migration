<?php

namespace App\Repository;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionDossierAssociation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionDossierAssociation>
 *
 * @method PrescriptionDossierAssociation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionDossierAssociation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionDossierAssociation[]    findAll()
 * @method PrescriptionDossierAssociation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionDossierAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionDossierAssociation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionDossierAssociation $entity, bool $flush = true): void
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
    public function remove(PrescriptionDossierAssociation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remplace toutes les occurrences d'un article par un autre dans les associations de dossier de prescription.
     *
     * @param PrescriptionArticleListe $oldArticle L'article existant à remplacer
     * @param PrescriptionArticleListe $newArticle le nouvel article à associer
     */
    public function replaceAllArticles(PrescriptionArticleListe $oldArticle, PrescriptionArticleListe $newArticle): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(PrescriptionDossierAssociation::class, 'pda')
            ->set('pda.article', ':newArticle')
            ->where('pda.article = :oldArticle')
            ->setParameter('newArticle', $newArticle)
            ->setParameter('oldArticle', $oldArticle)
            ->getQuery()
            ->execute()
        ;
    }
}
