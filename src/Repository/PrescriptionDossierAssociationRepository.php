<?php

namespace App\Repository;

use App\Entity\PrescriptionDossierAssociation;
use App\Entity\PrescriptionTexteListe;
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
     * Remplace tous les textes d'une entité PrescriptionDossierAssociation par un nouveau texte.
     *
     * @param PrescriptionTexteListe $oldText L'ancien texte à remplacer
     * @param PrescriptionTexteListe $newText le nouveau texte à appliquer
     */
    public function replaceAllTexts(PrescriptionTexteListe $oldText, PrescriptionTexteListe $newText): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(PrescriptionDossierAssociation::class, 'pda')
            ->set('pda.texte', ':newText')
            ->where('pda.texte = :oldText')
            ->setParameter('newText', $newText)
            ->setParameter('oldText', $oldText)
            ->getQuery()
            ->execute()
        ;
    }
}
