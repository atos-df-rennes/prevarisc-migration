<?php

namespace App\Repository;

use App\Entity\Resources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resources>
 *
 * @method Resources|null find($id, $lockMode = null, $lockVersion = null)
 * @method Resources|null findOneBy(array $criteria, array $orderBy = null)
 * @method Resources[]    findAll()
 * @method Resources[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResourcesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resources::class);
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesDossier(): array
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Resources::class, 'r');
        $rsm->addFieldResult('r', 'id_resource', 'id');
        $rsm->addFieldResult('r', 'name', 'name');
        $rsm->addFieldResult('r', 'text', 'text');

        // REGEXP '^dossier_[0-9]+$' sélectionne uniquement les noms du type 'dossier_<nombre>'
        $sql = "SELECT * FROM resources WHERE name REGEXP '^dossier_[0-9]+$'";
        $query = $em->createNativeQuery($sql, $rsm);

        return $query->getResult();
    }
}
