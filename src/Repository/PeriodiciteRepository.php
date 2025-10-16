<?php

namespace App\Repository;

use App\Entity\Etablissement;
use App\Entity\Periodicite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Periodicite>
 *
 * @method Periodicite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Periodicite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Periodicite[]    findAll()
 * @method Periodicite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeriodiciteRepository extends ServiceEntityRepository
{
    public const ID_GENRE_ETABLISSEMENT = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Periodicite::class);
    }

    /**
     * Récupère une entité Periodicite en fonction de la catégorie, du type et du local de sommeil.
     *
     * @param int $categorie La catégorie spécifique de la periodicité.
     * @param int $type Le type spécifique de la periodicité.
     * @param string $localSommeil Le local de sommeil associé à la periodicité.
     *
     * @return Periodicite|null Renvoie l'entité Periodicite correspondante ou null si aucune correspondance trouvée.
     */
    public function gn4(int $categorie, int $type, string $localSommeil): ?Periodicite
    {
        // On prépare la requête SQL
        $queryBuilder = $this->createQueryBuilder('periodicite');
        $queryBuilder
            ->where('periodicite.categorie = :categorie')
            ->andWhere('periodicite.type = :type')
            ->andWhere('periodicite.localSommeil = :localSommeil')
            ->setParameter('categorie', $categorie)
            ->setParameter('type', $type)
            ->setParameter('localSommeil', $localSommeil)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Génère une chaîne spécifique pour un établissement donné en fonction de ses informations.
     *
     * @param array $etablissement Tableau associatif contenant les informations de l'établissement.
     *
     * @return string|null Renvoie une chaîne spécifique si les conditions sont remplies, sinon null.
     */
    public function gn4ForEtablissement(Etablissement $etablissement): ?Periodicite
    {
        $information = $etablissement->getLatestInformations();

        if (!in_array($information->getGenre()->getId(), [2,5])) {
            return null;
        }

        $type = self::ID_GENRE_ETABLISSEMENT === $information->getGenre()->getId() ? $information->getType() : $information->getClasse();

        dump($type);
        return $this->gn4($information->getCategorie()->getId(), $type->getId(), 'todo');
    }

    // /**
    //  * @return Periodicite[] Returns an array of Periodicite objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Periodicite
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
