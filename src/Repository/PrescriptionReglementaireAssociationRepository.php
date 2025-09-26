<?php

namespace App\Repository;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionReglementaireAssociation;
use App\Entity\PrescriptionTexteListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
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
     * Récupère les associations de prescription réglementaire pour un identifiant donné.
     *
     * @param int $idPrescriptionRegl identifiant de la prescription réglementaire
     *
     * @return array<int, PrescriptionReglementaireAssociation|PrescriptionTexteListe|PrescriptionArticleListe>
     */
    public function getPrescriptionReglAssoc(int $idPrescriptionRegl): array
    {
        // Crée la requête QueryBuilder
        $qb = $this->createQueryBuilder('pra')
            ->addSelect('pal', 'ptl')
            ->join(PrescriptionArticleListe::class, 'pal', Join::WITH, 'pal.id = pra.article')
            ->join(PrescriptionTexteListe::class, 'ptl', Join::WITH, 'ptl.id = pra.texte')
            ->where('pra.regle = :idPrescriptionRegl')
            ->setParameter('idPrescriptionRegl', $idPrescriptionRegl)
            ->orderBy('pra.numero');

        // Exécute la requête et retourne les résultats
        return $qb->getQuery()->getResult();
    }

    /**
     * Remplace toutes les occurrences d'un texte réglementaire par un nouveau texte.
     *
     * @param PrescriptionTexteListe $oldText L'ancien texte à remplacer
     * @param PrescriptionTexteListe $newText le nouveau texte à utiliser
     */
    public function replaceAllTexts(PrescriptionTexteListe $oldText, PrescriptionTexteListe $newText): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(PrescriptionReglementaireAssociation::class, 'pra')
            ->set('pra.texte', ':newText')
            ->where('pra.texte = :oldText')
            ->setParameter('newText', $newText)
            ->setParameter('oldText', $oldText)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Remplace toutes les occurrences d'un ancien article par un nouveau article dans les associations réglementaires.
     *
     * @param PrescriptionArticleListe $oldArticle L'ancien article à remplacer
     * @param PrescriptionArticleListe $newArticle le nouvel article qui remplace l'ancien
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
