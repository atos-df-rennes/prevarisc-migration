<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Utilisateur $entity, bool $flush = true): void
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
    public function remove(Utilisateur $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
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
            ->join('u.informations', 'ui')
            ->join('ui.fonction', 'f')
            ->join('u.groupe', 'g')
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
            ->join('u.informations', 'ui')
            ->join('ui.fonction', 'f')
            ->join('u.groupe', 'g')
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
}
