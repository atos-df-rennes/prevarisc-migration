<?php

namespace App\Repository;

use App\Entity\EtablissementInformations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementInformations>
 *
 * @method EtablissementInformations|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementInformations|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementInformations[]    findAll()
 * @method EtablissementInformations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementInformationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementInformations::class);
    }

    /**
     * @param int $genre Type d'établissement : 1 = Site, 2 = Établissement, 3 = Cellule
     *
     * @return array<int, array{etablissementInformations: EtablissementInformations, utilisateurId: int}>
     */
    public function getWithPreventionnistes(string $groupement, int $genre): array
    {
        $subQb = $this->createQueryBuilder('ei2');
        $subQb->select('MAX(ei2.dateEtablissementinformations)')
            ->where('ei2.etablissement = ei.etablissement');

        $qb = $this->createQueryBuilder('ei');
        $qb->select('ei', 'u.id AS utilisateurId')
            ->innerJoin('ei.etablissement', 'e');

        // Joins spécifiques selon le type
        switch ($genre) {
            case 1: // Sites
                $qb->innerJoin('e.etablissementLies', 'el')
                    ->innerJoin('el.filsEtablissement', 'fils')
                    ->innerJoin('fils.adresses', 'ea');
                break;
            case 3: // Cellules
                $qb->innerJoin('e.etablissementLiesFils', 'el')
                    ->innerJoin('el.etablissement', 'pere')
                    ->innerJoin('pere.adresses', 'ea');
                break;
            case 2: // Établissements
            default:
                $qb->innerJoin('e.adresses', 'ea');
                break;
        }

        $qb->innerJoin('ea.commune', 'c')
            ->innerJoin('c.groupementCommunes', 'gc')
            ->innerJoin('gc.groupement', 'g')
            ->innerJoin('g.groupementPreventionnistes', 'gp')
            ->innerJoin('gp.utilisateur', 'u')
            ->where('ei.genre = :genre')
            ->andWhere('g.libelleGroupement = :groupement')
            ->andWhere($qb->expr()->eq('ei.dateEtablissementinformations', '('.$subQb->getDQL().')'))
            ->setParameter('genre', $genre)
            ->setParameter('groupement', $groupement);

        $results = $qb->getQuery()->getResult();

        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'etablissementInformations' => $result[0],
                'utilisateurId' => $result['utilisateurId'],
            ];
        }

        return $data;
    }

    /**
     * @return array<int, array{etablissementInformations: EtablissementInformations, utilisateurId: int}>
     */
    public function getEtablissementsWithPreventionnistes(string $groupement): array
    {
        return $this->getWithPreventionnistes($groupement, 2);
    }

    /**
     * @return array<int, array{etablissementInformations: EtablissementInformations, utilisateurId: int}>
     */
    public function getCellulesWithPreventionnistes(string $groupement): array
    {
        return $this->getWithPreventionnistes($groupement, 3);
    }

    /**
     * @return array<int, array{etablissementInformations: EtablissementInformations, utilisateurId: int}>
     */
    public function getSitesWithPreventionnistes(string $groupement): array
    {
        return $this->getWithPreventionnistes($groupement, 1);
    }

    /**
     * @param array<string> $communesNumInsee
     *
     * @return EtablissementInformations[]
     */
    public function findByCommunes(array $communesNumInsee, bool $filterByLastDate = false): array
    {
        if (empty($communesNumInsee)) {
            return [];
        }

        $qb = $this->createQueryBuilder('ei');
        $qb->select('ei')
            ->innerJoin('ei.etablissement', 'e')
            ->innerJoin('e.adresses', 'ea')
            ->innerJoin('ea.commune', 'c')
            ->where('c.id IN (:communes)')
            ->setParameter('communes', $communesNumInsee);

        if ($filterByLastDate) {
            $subQb = $this->createQueryBuilder('ei2');
            $subQb->select('MAX(ei2.date)')
                ->where('ei2.etablissement = ei.etablissement');

            $qb->andWhere($qb->expr()->eq('ei.dateEtablissementinformations', '('.$subQb->getDQL().')'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param EtablissementInformations[] $etablissementInformations
     *
     * @return EtablissementInformations[]
     */
    public function filterLastInformations(array $etablissementInformations): array
    {
        $etablissementInfosByEtablissement = [];

        foreach ($etablissementInformations as $etablissementInfo) {
            $etablissement = $etablissementInfo->getEtablissement();
            if (null === $etablissement) {
                continue;
            }

            $etablissementId = $etablissement->getId();
            $date = $etablissementInfo->getDate();

            if (!isset($etablissementInfosByEtablissement[$etablissementId])) {
                $etablissementInfosByEtablissement[$etablissementId] = $etablissementInfo;
            } else {
                $currentDate = $etablissementInfosByEtablissement[$etablissementId]->getDate();
                if ($date > $currentDate) {
                    $etablissementInfosByEtablissement[$etablissementId] = $etablissementInfo;
                }
            }
        }

        return array_values($etablissementInfosByEtablissement);
    }

    /**
     * @param array<string> $communesNumInsee
     *
     * @return EtablissementInformations[]
     */
    public function findCellulesByCommunes(array $communesNumInsee): array
    {
        if (empty($communesNumInsee)) {
            return [];
        }

        $qb = $this->createQueryBuilder('ei');
        $qb->select('ei')
            ->innerJoin('ei.etablissement', 'e')
            ->innerJoin('e.etablissementLiesFils', 'el')
            ->innerJoin('el.etablissement', 'pere')
            ->innerJoin('pere.adresses', 'ea')
            ->innerJoin('ea.commune', 'c')
            ->where('c.id IN (:communes)')
            ->setParameter('communes', $communesNumInsee);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string> $communesNumInsee
     *
     * @return EtablissementInformations[]
     */
    public function findSitesByCommunes(array $communesNumInsee): array
    {
        if (empty($communesNumInsee)) {
            return [];
        }

        $qb = $this->createQueryBuilder('ei');
        $qb->select('ei')
            ->innerJoin('ei.etablissement', 'e')
            ->innerJoin('e.etablissementLies', 'el')
            ->innerJoin('el.filsEtablissement', 'fils')
            ->innerJoin('fils.adresses', 'ea')
            ->innerJoin('ea.commune', 'c')
            ->where('c.id IN (:communes)')
            ->setParameter('communes', $communesNumInsee);

        return $qb->getQuery()->getResult();
    }
}
