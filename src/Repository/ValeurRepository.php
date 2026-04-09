<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Valeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Valeur>
 *
 * @method Valeur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Valeur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Valeur[]    findAll()
 * @method Valeur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Valeur::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Valeur $entity, bool $flush = true): void
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
    public function remove(Valeur $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne toutes les Valeur liées à un Dossier, indexées par ID de Champ.
     * Pour les champs tableau, plusieurs valeurs par champ sont possibles (groupées par idx).
     *
     * @return array<int, Valeur[]>
     */
    public function findByDossier(Dossier $dossier): array
    {
        $valeurs = $this->getEntityManager()->createQuery(
            'SELECT v, c FROM App\Entity\Valeur v
             JOIN v.champ c
             JOIN App\Entity\Dossier d
             JOIN d.valeurs dv
             WHERE d = :dossier AND dv = v
             ORDER BY v.idx ASC'
        )
            ->setParameter('dossier', $dossier)
            ->getResult()
        ;

        return $this->indexByChamp($valeurs);
    }

    /**
     * Retourne toutes les Valeur liées à un Etablissement, indexées par ID de Champ.
     *
     * @return array<int, Valeur[]>
     */
    public function findByEtablissement(Etablissement $etablissement): array
    {
        $valeurs = $this->getEntityManager()->createQuery(
            'SELECT v, c FROM App\Entity\Valeur v
             JOIN v.champ c
             JOIN App\Entity\Etablissement e
             JOIN e.valeurs ev
             WHERE e = :etablissement AND ev = v
             ORDER BY v.idx ASC'
        )
            ->setParameter('etablissement', $etablissement)
            ->getResult()
        ;

        return $this->indexByChamp($valeurs);
    }

    /**
     * Groupe les valeurs par ID de Champ.
     *
     * @param Valeur[] $valeurs
     *
     * @return array<int, Valeur[]>
     */
    private function indexByChamp(array $valeurs): array
    {
        $indexed = [];
        foreach ($valeurs as $valeur) {
            $champId = $valeur->getChamp()->getId();
            $indexed[$champId][] = $valeur;
        }

        return $indexed;
    }
}
