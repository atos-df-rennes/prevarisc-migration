<?php

namespace App\Repository;

use App\Entity\PrescriptionReglementaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionReglementaire>
 *
 * @method PrescriptionReglementaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionReglementaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionReglementaire[]    findAll()
 * @method PrescriptionReglementaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionReglementaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionReglementaire::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionReglementaire $entity, bool $flush = true): void
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
    public function remove(PrescriptionReglementaire $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère une liste de prescriptions réglementaires en fonction du type et du mode.
     *
     * @param string    $type le type de prescription (par exemple 'etude' ou 'visite')
     * @param bool|null $mode optionnel, si défini, filtre les prescriptions visibles
     *
     * @return PrescriptionReglementaire[] la liste des prescriptions réglementaires correspondant aux critères
     */
    public function recupPrescRegl(string $type, ?bool $mode = null): array
    {
        switch ($type) {
            case 'etude': $typePresc = 1;
                break;
            case 'visite': $typePresc = 2;
                break;
            default: $typePresc = null;
        }

        // Crée la requête QueryBuilder
        $qb = $this->createQueryBuilder('pre')
            ->addSelect('a')
            ->join('pre.associations', 'a')
            ->where('pre.type = :typePresc')
            ->setParameter('typePresc', $typePresc);

        if (null !== $mode) {
            $qb->andWhere('pre.visible = :mode')
                ->setParameter('mode', $mode)
            ;
        }

        // Exécute la requête et retourne les résultats
        return $qb->getQuery()->getResult();
    }
}
