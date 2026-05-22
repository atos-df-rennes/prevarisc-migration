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
    private const FONCTION_PREVENTIONNISTE = 13;

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
     * Crée un QueryBuilder pour récupérer les préventionnistes.
     * Utilisé dans les formulaires avec EntityType (ex: GroupementType).
     */
    public function createPreventionnistesQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.informations', 'i')
            ->innerJoin('i.fonction', 'f')
            ->where('f.id = :fonction')
            ->setParameter('fonction', self::FONCTION_PREVENTIONNISTE)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC');
    }

    /**
     * Trouve les préventionnistes valides pour la validation du formulaire dossier.
     *
     * Règle métier :
     * - Les IDs soumis qui correspondent à un préventionniste actif sont acceptés (nouveau choix)
     * - Les IDs soumis qui correspondent à un préventionniste déjà affecté sont acceptés (conservation, même inactif)
     *
     * @param int[] $submittedIds IDs soumis par le formulaire (via TomSelect)
     * @param int[] $existingIds  IDs des préventionnistes déjà affectés au dossier
     *
     * @return Utilisateur[]
     */
    public function findValidPreventionnistesForSubmit(array $submittedIds, array $existingIds = []): array
    {
        if (empty($submittedIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.informations', 'i')
            ->innerJoin('i.fonction', 'f')
            ->where('f.id = :fonction')
            ->setParameter('fonction', self::FONCTION_PREVENTIONNISTE)
            ->andWhere('u.id IN (:submittedIds)')
            ->setParameter('submittedIds', $submittedIds)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC');

        if (!empty($existingIds)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'u.actif = true',
                    $qb->expr()->in('u.id', ':existingIds')
                )
            )->setParameter('existingIds', $existingIds);
        } else {
            $qb->andWhere('u.actif = true');
        }

        return $qb->getQuery()->getResult();
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

    /**
     * Trouve les utilisateurs destinataires pour une alerte.
     *
     * Équivalent legacy : Model_DbTable_Utilisateur::findUtilisateursForAlerte()
     *
     * Récupère les utilisateurs ayant :
     * - Le privilege spécifié (ex: alerte_avis)
     * - Une adresse email valide (non null, non vide)
     * - Un lien avec la commune (directement OU via groupement)
     *
     * @param string $privilegeName Nom du privilege requis (ex: 'alerte_avis')
     * @param string $numInsee      Numéro INSEE de la commune
     *
     * @return array<int, array{id: int, nom: string, prenom: string, mail: string}> Liste utilisateurs
     */
    public function findUtilisateursForAlerte(string $privilegeName, string $numInsee): array
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('DISTINCT u.id as id', 'ui.nom as nom', 'ui.prenom as prenom', 'ui.mail as mail')
            ->join('u.informations', 'ui')
            ->join('u.groupe', 'g')
            ->join('g.privileges', 'p')
            ->leftJoin('u.groupements', 'ug')
            ->leftJoin('ug.communes', 'gc')
            ->where('ui.mail IS NOT NULL')
            ->andWhere('ui.mail != :emptyMail')
            ->andWhere('p.name = :privilegeName')
            ->andWhere($qb->expr()->orX(
                'u.commune = :numInsee',  // Commune directe
                'gc.id = :numInsee'       // Commune via groupement
            ))
            ->setParameter('emptyMail', '')
            ->setParameter('privilegeName', $privilegeName)
            ->setParameter('numInsee', $numInsee)
            ->groupBy('u.id')
        ;

        /** @var array<int, array{id: int, nom: string, prenom: string, mail: string}> $results */
        $results = $qb->getQuery()->getResult();

        return $results;
    }

    /**
     * @return Utilisateur[]
     */
    public function findExistingByMail(string $mail, ?Utilisateur $utilisateur = null): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->join('u.informations', 'i')
            ->where('i.mail = :mail')
            ->setParameter('mail', $mail)
        ;

        // Ne prend pas en compte l'utilisateur en cours d'édition
        if (null !== $utilisateur) {
            $queryBuilder->andWhere('u.id != :utilisateurId')
                ->setParameter('utilisateurId', $utilisateur->getId())
            ;
        }

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }
}
