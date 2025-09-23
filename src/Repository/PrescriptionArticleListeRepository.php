<?php

namespace App\Repository;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionTexteListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionArticleListe>
 *
 * @method PrescriptionArticleListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionArticleListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionArticleListe[]    findAll()
 * @method PrescriptionArticleListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionArticleListeRepository extends ServiceEntityRepository
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
        parent::__construct($registry, PrescriptionArticleListe::class);
        $this->prescriptionDossierAssociationRepository = $prescriptionDossierAssociationRepository;
        $this->prescriptionTypeAssociationRepository = $prescriptionTypeAssociationRepository;
        $this->prescriptionReglementaireAssociationRepository = $prescriptionReglementaireAssociationRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionArticleListe $entity, bool $flush = true): void
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
    public function remove(PrescriptionArticleListe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remplace un article par un nouveau et supprime l'ancien dans toutes les associations liées.
     *
     * Cette méthode utilise une transaction pour garantir l'intégrité des données lors du remplacement et
     * de la suppression de l'entité spécifique.
     *
     * @param PrescriptionArticleListe $entity L'entité existante à remplacer et à supprimer.
     * @param PrescriptionArticleListe $newEntity La nouvelle entité qui remplacera l'ancienne.
     *
     * @return void
     */
    public function replaceAndRemove(PrescriptionArticleListe $entity, PrescriptionArticleListe $newEntity): void
    {
        $em = $this->getEntityManager();
        $em->wrapInTransaction(function ($em) use ($entity, $newEntity): void {
            $this->prescriptionDossierAssociationRepository->replaceAllArticles($entity, $newEntity);
            $this->prescriptionTypeAssociationRepository->replaceAllArticles($entity, $newEntity);
            $this->prescriptionReglementaireAssociationRepository->replaceAllArticles($entity, $newEntity);

            $em->remove($entity);
        });
    }
}
