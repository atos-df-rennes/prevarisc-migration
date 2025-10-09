<?php

namespace App\Repository;

use App\Entity\PrescriptionArticle;
use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionReglementaireAssociation;
use App\Entity\PrescriptionTexte;
use App\Entity\PrescriptionTexteListe;
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
     * Récupère les associations de prescription réglementaire pour un identifiant donné.
     *
     * @return array<int, PrescriptionTypeAssociation|PrescriptionTexte|PrescriptionArticle>
     */
    public function getPrescriptionAssoc(int $prescriptionId): array
    {
        $qb = $this->createQueryBuilder('association')
            ->addSelect('texte', 'article')
            ->leftJoin('association.texte', 'texte')
            ->leftJoin('association.article', 'article')
            ->where('association.prescriptionType = :idPrescription')
            ->setParameter('idPrescription', $prescriptionId)
            ->orderBy('association.numero');

        return $qb->getQuery()->getResult();
    }

    /**
     * Remplace tous les textes associés dans l'entité PrescriptionTypeAssociation.
     *
     * Cette méthode remplace les occurrences d'un texte existant par un nouveau texte,
     * en mettant à jour directement l'entité correspondante dans la base de données.
     *
     * @param PrescriptionTexteListe $oldText L'ancien texte à remplacer
     * @param PrescriptionTexteListe $newText le nouveau texte à définir
     */
    public function replaceAllTexts(PrescriptionTexteListe $oldText, PrescriptionTexteListe $newText): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(PrescriptionTypeAssociation::class, 'pta')
            ->set('pta.texte', ':newText')
            ->where('pta.texte = :oldText')
            ->setParameter('newText', $newText)
            ->setParameter('oldText', $oldText)
            ->getQuery()
            ->execute()
        ;
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
