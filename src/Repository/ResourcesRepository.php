<?php

namespace App\Repository;

use App\Entity\Classement;
use App\Entity\EtablissementActivite;
use App\Entity\Famille;
use App\Entity\IghClasse;
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

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findRessourcesSpecialiseesEtablissementCamping(): Resources
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $query = $queryBuilder
            ->where('r.name = :name')
            ->setParameter('name', 'etablissement_camp_0_0')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
        ;

        return $query->getSingleResult();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findRessourcesSpecialiseesEtablissementIop(): Resources
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $query = $queryBuilder
            ->where('r.name = :name')
            ->setParameter('name', 'etablissement_iop_0_0')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
        ;

        return $query->getSingleResult();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findRessourcesSpecialiseesEtablissementManifestationTemporaire(): Resources
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $query = $queryBuilder
            ->where('r.name = :name')
            ->setParameter('name', 'etablissement_temp_0_0')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
        ;

        return $query->getSingleResult();
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesEtablissementZone(?Classement $classement): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.name = :tousClassements')
            ->setParameter('tousClassements', 'etablissement_zone_0_0_0')
        ;

        if (null !== $classement) {
            $queryBuilder
                ->orWhere('r.name LIKE :classement')
                ->setParameter('classement', 'etablissement\_zone\_%'.$classement->getId().'%\_0\_0');
        }

        $query = $queryBuilder
            ->orderBy('r.name', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesEtablissementBup(): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.name LIKE :name')
            ->setParameter('name', 'etablissement\_bup\_0\__\__')
        ;
        $query = $queryBuilder
            ->orderBy('r.name', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesEtablissementHabitation(?Famille $famille): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.name LIKE :toutesFamilles')
            ->setParameter('toutesFamilles', 'etablissement\_hab\_0\__\__')
        ;

        if (null !== $famille) {
            $queryBuilder
                ->orWhere('r.name LIKE :famille')
                ->setParameter('famille', 'etablissement\_hab\_%'.$famille->getId().'%\__\__')
            ;
        }

        $query = $queryBuilder
            ->orderBy('r.name', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesEtablissementIgh(?IghClasse $classe): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.name LIKE :toutesClasses')
            ->setParameter('toutesClasses', 'etablissement\_igh\_0\__\__\__')
        ;

        if (null !== $classe) {
            $queryBuilder
                ->orWhere('r.name LIKE :classe')
                ->setParameter('classe', 'etablissement\_igh\_%'.$classe->getId().'%\__\__\__')
            ;
        }

        $query = $queryBuilder
            ->orderBy('r.name', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesEtablissementCellule(?EtablissementActivite $typeActivite): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.name = :toutesClasses')
            ->setParameter('toutesClasses', 'etablissement_cell_0_0')
        ;

        if (null !== $typeActivite) {
            $queryBuilder
                ->orWhere('r.name LIKE :classe')
                ->setParameter('classe', 'etablissement\_cell\_%'.$typeActivite->getId().'%\_0')
            ;
        }

        $query = $queryBuilder
            ->orderBy('r.name', 'ASC')
            ->getQuery();

        return $query->getResult();
    }
}
