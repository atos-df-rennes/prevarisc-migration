<?php

namespace App\Repository;

use App\Entity\PrescriptionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionType>
 *
 * @method PrescriptionType|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionType|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionType[]    findAll()
 * @method PrescriptionType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionType::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionType $entity, bool $flush = true): void
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
    public function remove(PrescriptionType $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne les prescriptions types filtrées par classement (catégorie, texte, article).
     * Une valeur 0 sur un paramètre signifie "non filtré".
     *
     * @return PrescriptionType[]
     */
    public function findByClassement(int $categorieId, int $texteId, int $articleId): array
    {
        $qb = $this->createQueryBuilder('pt')
            ->orderBy('pt.numero', 'ASC');

        if (0 !== $categorieId) {
            $qb->andWhere('pt.categorie = :categorie')
                ->setParameter('categorie', $categorieId);
        }

        if (0 !== $texteId) {
            $qb->andWhere('pt.texte = :texte')
                ->setParameter('texte', $texteId);
        }

        if (0 !== $articleId) {
            $qb->andWhere('pt.article = :article')
                ->setParameter('article', $articleId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche des prescriptions types par mots-clés.
     * La recherche porte sur le libellé de la prescription, les textes et les articles associés.
     *
     * @param string[] $motsCles
     *
     * @return PrescriptionType[]
     */
    public function findByMotsCles(array $motsCles): array
    {
        if ([] === $motsCles) {
            return [];
        }

        $qb = $this->createQueryBuilder('pt')
            ->leftJoin('pt.associations', 'pta')
            ->leftJoin('pta.texte', 'ptl')
            ->leftJoin('pta.article', 'pal')
            ->distinct();

        foreach ($motsCles as $index => $mot) {
            $paramLibelle = 'libelle_'.$index;
            $paramTexte = 'texte_'.$index;
            $paramArticle = 'article_'.$index;

            $qb->orWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('pt.libelle', ':'.$paramLibelle),
                    $qb->expr()->like('ptl.libelle', ':'.$paramTexte),
                    $qb->expr()->like('pal.libelle', ':'.$paramArticle)
                )
            )
            ->setParameter($paramLibelle, '%'.$mot.'%')
            ->setParameter($paramTexte, '%'.$mot.'%')
            ->setParameter($paramArticle, '%'.$mot.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @todo: il faudrait idéalement avoir l'id de la catégorie, du texte et de l'article
     * pour aligner les identifiants d'un même groupe.
     *
     * Met à jour l'ordre en fonction d'un tableau fourni.
     *
     * @param array $order tableau contenant l'ordre des éléments (identifiants)
     */
    public function setOrder(array $order): void
    {
        $em = $this->getEntityManager();
        $elements = $this->findBy(['id' => $order]);

        foreach ($elements as $element) {
            $position = array_search($element->getId(), $order, true);
            $element->setNumero($position + 1);
        }

        $em->flush();
    }

    /**
     * Récupère le numéro maximal.
     *
     * @return int le numéro maximal
     */
    public function recupMaxNumCat(): int
    {
        $qb = $this->createQueryBuilder('prescription_type');
        $qb->select('max(prescription_type.numero)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
