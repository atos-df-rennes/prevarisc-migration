<?php

namespace App\Repository;

use App\Entity\Resources;
use App\Security\Voter\EtablissementVoter;
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
     * @param int $genre le genre d'établissement à filtrer via son identifiant
     *
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesEtablissement(int $genre): array
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Resources::class, 'r');
        $rsm->addFieldResult('r', 'id_resource', 'id');
        $rsm->addFieldResult('r', 'name', 'name');
        $rsm->addFieldResult('r', 'text', 'text');

        // Liste des constantes de règles
        $regles = [
            2 => EtablissementVoter::REGLE_ERP,
            3 => EtablissementVoter::REGLE_CELLULE,
            4 => EtablissementVoter::REGLE_HABITATION,
            5 => EtablissementVoter::REGLE_IGH,
            6 => EtablissementVoter::REGLE_BUP,
            7 => EtablissementVoter::REGLE_CAMPING,
            8 => EtablissementVoter::REGLE_MANIFESTATION_TEMPORAIRE,
            9 => EtablissementVoter::REGLE_IOP,
            10 => EtablissementVoter::REGLE_ZONE,
        ];

        if (!array_key_exists($genre, $regles)) {
            throw new \InvalidArgumentException('Genre invalide : '.$genre);
        }

        $regle = $regles[$genre];
        $sql = "SELECT * FROM resources WHERE name REGEXP '".$regle."'";
        $query = $em->createNativeQuery($sql, $rsm);

        return $query->getResult();
    }
}
