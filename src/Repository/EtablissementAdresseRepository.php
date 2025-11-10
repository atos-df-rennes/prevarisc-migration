<?php

namespace App\Repository;

use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementAdresse>
 *
 * @method EtablissementAdresse|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementAdresse|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementAdresse[]    findAll()
 * @method EtablissementAdresse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementAdresseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementAdresse::class);
    }

    /**
     * Récupère les informations liées à un établissement en fonction de son identifiant.
     *
     * @param int $idEtablissement L'identifiant de l'établissement
     *
     * @return array les informations de l'établissement ou un tableau vide si aucun résultat n'est trouvé
     */
    public function get(int $idEtablissement): array
    {
        $etablissementRepository = $this->_em->getRepository(Etablissement::class);
        $etablissement = $etablissementRepository->find($idEtablissement);

        if (!$etablissement) {
            return [];
        }

        $informations = $etablissement->getInformations()->last();
        if (!$informations) {
            return [];
        }

        $genre = $informations->getGenre();
        if (!$genre) {
            return [];
        }

        switch ($genre->getId()) {
            // Adresse d'un site
            case 1:
                $enfants = $this->getEnfantsAvecEffectifs($idEtablissement);

                if (!empty($enfants)) {
                    $enfantMaxEffectif = $this->getEnfantAvecMaxEffectif($enfants);

                    return $this->get($enfantMaxEffectif['id']);
                }

                return [];

                // Adresse d'une cellule
            case 3:
                $parent = $this->getParentRacine($etablissement);

                if (null !== $parent) {
                    return $this->get($parent->getId());
                }

                return [];

                // Adresse par défaut
            default:
                return $this->getAdressesAvecDetails($idEtablissement);
        }
    }

    /**
     * Récupère les informations des enfants d'un établissement donnés ainsi que leurs effectifs.
     *
     * @param int $idEtablissement identifiant de l'établissement parent
     *
     * @return array résultat contenant les informations sur les enfants (ID, effectif public, effectif personnel)
     */
    private function getEnfantsAvecEffectifs(int $idEtablissement): array
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('e.id', 'ei.effectifPublic', 'ei.effectifPersonnel')
            ->from(Etablissement::class, 'e')
            ->join('e.parents', 'ep')
            ->join('e.informations', 'ei')
            ->where('ep.id = :etablissementId')
            ->andWhere('ei.date = (SELECT MAX(ei2.date) FROM App\Entity\EtablissementInformations ei2 WHERE ei2.etablissement = e.id)')
            ->setParameter('etablissementId', $idEtablissement);

        return $qb->getQuery()->getResult();
    }

    /**
     * Détermine l'enfant avec le plus grand effectif total.
     *
     * @param array $enfants Liste d'enfants contenant les informations d'effectif.
     *                       Chaque enfant doit être un tableau avec les clés 'effectifPublic' (int) et/ou 'effectifPersonnel' (int).
     *
     * @return array L'enfant avec le plus grand effectif total (somme d'effectifPublic et effectifPersonnel)
     */
    private function getEnfantAvecMaxEffectif(array $enfants): array
    {
        $maxEffectif = 0;
        $enfantMax = $enfants[0];

        foreach ($enfants as $enfant) {
            $effectifTotal = ($enfant['effectifPublic'] ?? 0) + ($enfant['effectifPersonnel'] ?? 0);
            if ($effectifTotal > $maxEffectif) {
                $maxEffectif = $effectifTotal;
                $enfantMax = $enfant;
            }
        }

        return $enfantMax;
    }

    /**
     * Récupère l'établissement racine parent d'un établissement donné.
     *
     * Parcourt la hiérarchie des parents d'un établissement pour trouver le parent racine.
     * Retourne null si l'établissement fourni est déjà à la racine.
     *
     * @param Etablissement $etablissement L'établissement pour lequel chercher le parent racine
     *
     * @return Etablissement|null le parent racine si trouvé, ou null si l'établissement est déjà à la racine
     */
    private function getParentRacine(Etablissement $etablissement): ?Etablissement
    {
        $current = $etablissement;
        $parent = $current->getParent();

        while (null !== $parent) {
            $current = $parent;
            $parent = $current->getParent();
        }

        return $current !== $etablissement ? $current : null;
    }

    /**
     * Récupère les adresses avec leurs détails associés pour un établissement donné.
     *
     * @param int $idEtablissement identifiant de l'établissement pour lequel récupérer les adresses
     *
     * @return array retourne un tableau contenant les adresses avec leurs données associées (commune, rue, type de rue)
     */
    private function getAdressesAvecDetails(int $idEtablissement): array
    {
        $qb = $this->createQueryBuilder('ea');
        $qb->select('ea', 'c', 'r', 'rt')
            ->leftJoin('ea.commune', 'c')
            ->leftJoin('ea.rue', 'r')
            ->leftJoin('r.type', 'rt')
            ->where('ea.etablissement = :etablissementId')
            ->setParameter('etablissementId', $idEtablissement);

        return $qb->getQuery()->getResult();
    }
}
