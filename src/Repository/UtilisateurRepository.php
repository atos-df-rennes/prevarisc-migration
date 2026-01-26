<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 *
 * @method Utilisateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utilisateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utilisateur[]    findAll()
 * @method Utilisateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Renvoie les utilisateurs actifs dont la dernière action date de moins d'un jour.
     */
    public function findConnected(): array
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $query = $queryBuilder
            ->addSelect('ui')
            ->addSelect('f')
            ->addSelect('g')
            ->join('u.informations', 'ui')
            ->join('ui.fonction', 'f')
            ->join('u.groupe', 'g')
            ->where('u.actif = :actif')
            ->andWhere($queryBuilder->expr()->gt('u.lastAction', ':session'))
            ->setParameter('actif', 1)
            ->setParameter('session', date('Y-m-d H:i:s', time() - ini_get('session.gc_maxlifetime')))
            ->groupBy('u.id')
            ->orderBy('ui.nom', 'ASC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    // @fixme: A voir pour faire une requête commune et ajouter un paramètre pour le statut actif ?
    /**
     * Renvoie les utilisateurs actifs.
     */
    public function findActives(?int $groupeId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $query = $queryBuilder
            ->addSelect('ui')
            ->addSelect('f')
            ->addSelect('g')
            ->addSelect('up')
            ->join('u.informations', 'ui')
            ->join('ui.fonction', 'f')
            ->join('u.groupe', 'g')
            ->join('u.preferences', 'up')
            ->where('u.actif = :actif')
            ->setParameter('actif', 1)
            ->groupBy('u.id')
            ->orderBy('ui.nom', 'ASC')
        ;

        if (null !== $groupeId) {
            $query->andWhere('g.id = :groupe')
                ->setParameter('groupe', $groupeId);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Renvoie les utilisateurs inactifs.
     */
    public function findInactives(?int $groupeId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $query = $queryBuilder
            ->addSelect('ui')
            ->addSelect('f')
            ->addSelect('g')
            ->addSelect('up')
            ->join('u.informations', 'ui')
            ->join('ui.fonction', 'f')
            ->join('u.groupe', 'g')
            ->join('u.preferences', 'up')
            ->where('u.actif = :actif')
            ->setParameter('actif', 0)
            ->groupBy('u.id')
            ->orderBy('ui.nom', 'ASC')
        ;

        if (null !== $groupeId) {
            $query->andWhere('g.id = :groupe')
                ->setParameter('groupe', $groupeId);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour sélectionner les préventionnistes actifs
     * Utilisé dans les formulaires avec EntityType.
     */
    public function createPreventionnistesQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.informations', 'i')
            ->innerJoin('i.fonction', 'f')
            ->where('f.libelle = :preventionniste')
            ->setParameter('preventionniste', 'Préventionniste')
            ->andWhere('u.actif = true')
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC');
    }

    /**
     * QueryBuilder pour sélectionner les préventionnistes actifs
     * + les utilisateurs déjà sélectionnés (même inactifs).
     *
     * Utilisé en édition pour conserver les préventionnistes inactifs déjà associés.
     *
     * @param iterable<Utilisateur> $existingUsers Utilisateurs déjà sélectionnés
     */
    public function createPreventionnistesWithExistingQueryBuilder(iterable $existingUsers = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.informations', 'i')
            ->innerJoin('i.fonction', 'f')
            ->where('f.libelle = :preventionniste')
            ->setParameter('preventionniste', 'Préventionniste');

        // Extraire les IDs des utilisateurs existants
        $existingIds = [];
        foreach ($existingUsers as $user) {
            if ($user instanceof Utilisateur) {
                $existingIds[] = $user->getId();
            }
        }

        // Inclure les utilisateurs actifs OU déjà sélectionnés
        if (!empty($existingIds)) {
            $qb->andWhere('u.actif = true OR u.id IN (:existing)')
                ->setParameter('existing', $existingIds);
        } else {
            $qb->andWhere('u.actif = true');
        }

        return $qb->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC');
    }

    /**
     * Recherche de préventionnistes par nom/prénom pour autocomplete.
     *
     * @param string $query      Terme de recherche (min 3 caractères)
     * @param int    $limit      Nombre max de résultats (défaut: 100)
     * @param bool   $activeOnly Si true, uniquement les utilisateurs actifs
     *
     * @return Utilisateur[]
     */
    public function searchPreventionnistes(string $query, int $limit = 100, bool $activeOnly = true): array
    {
        if (strlen($query) < 3) {
            return [];
        }

        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.informations', 'i')
            ->innerJoin('i.fonction', 'f')
            ->where('f.libelle = :preventionniste')
            ->setParameter('preventionniste', 'Préventionniste');

        if ($activeOnly) {
            $qb->andWhere('u.actif = true');
        }

        // Recherche sur nom OU prénom
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('LOWER(i.nom)', ':query'),
                $qb->expr()->like('LOWER(i.prenom)', ':query'),
                $qb->expr()->like(
                    "LOWER(CONCAT(i.nom, ' ', i.prenom))",
                    ':query'
                ),
                $qb->expr()->like(
                    "LOWER(CONCAT(i.prenom, ' ', i.nom))",
                    ':query'
                )
            )
        )
        ->setParameter('query', '%'.strtolower($query).'%')
        ->orderBy('i.nom', 'ASC')
        ->addOrderBy('i.prenom', 'ASC')
        ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
