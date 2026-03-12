<?php

namespace App\Repository;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\Periodicite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Periodicite::class);
    }

    /**
     * Récupère une entité Periodicite en fonction de la catégorie, du type et du local de sommeil.
     *
     * @param int    $categorie    la catégorie spécifique de la periodicité
     * @param int    $type         le type spécifique de la periodicité
     * @param string $localSommeil le local de sommeil associé à la periodicité
     *
     * @return Periodicite|null renvoie l'entité Periodicite correspondante ou null si aucune correspondance trouvée
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
     * Applique les périodicités du tableau à tous les établissements actifs.
     *
     * Met à jour la périodicité de la dernière fiche d'informations de chaque établissement
     * en se basant sur les valeurs configurées dans le tableau des périodicités.
     *
     * Note : utilise du SQL natif car l'UPDATE...INNER JOIN multi-table n'est pas supporté en DQL.
     *
     * @throws Exception
     */
    public function applyPeriodicites(): void
    {
        $sql = 'UPDATE etablissementinformations
                INNER JOIN periodicite
                    ON periodicite.ID_CATEGORIE = etablissementinformations.ID_CATEGORIE
                    AND periodicite.ID_TYPE = etablissementinformations.ID_TYPE
                    AND periodicite.LOCALSOMMEIL_PERIODICITE = etablissementinformations.LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS
                SET PERIODICITE_ETABLISSEMENTINFORMATIONS = periodicite.PERIODICITE_PERIODICITE
                WHERE etablissementinformations.ID_ETABLISSEMENTINFORMATIONS IN (
                    SELECT * FROM (
                        SELECT ID_ETABLISSEMENTINFORMATIONS
                        FROM etablissementinformations
                        INNER JOIN etablissement
                            ON etablissementinformations.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                        WHERE etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                            SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS)
                            FROM etablissementinformations
                            WHERE ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                        )
                    ) AS latest_infos
                )';

        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $connection->executeStatement($sql);
            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }
    }

    /**
     * Génère une chaîne spécifique pour un établissement donné en fonction de ses informations.
     *
     * @param Etablissement $etablissement tableau associatif contenant les informations de l'établissement
     *
     * @return Periodicite|null renvoie une chaîne spécifique si les conditions sont remplies, sinon null
     */
    public function gn4ForEtablissement(Etablissement $etablissement): ?Periodicite
    {
        $information = $etablissement->getLatestInformations();

        if (!in_array($information->getGenre()->getId(), [GenreEtablissement::ERP, GenreEtablissement::IGH])) {
            return null;
        }

        $type = GenreEtablissement::ERP === $information->getGenre()->getId() ? $information->getType() : $information->getClasse();

        $typeId = 0;
        if (null !== $type) {
            $typeId = $type->getId();
        }

        $categorieId = 0;
        if (($categorie = $information->getCategorie()) !== null) {
            $categorieId = $categorie->getId();
        }

        return $this->gn4($categorieId, $typeId, $information->getLocalSommeil());
    }
}
