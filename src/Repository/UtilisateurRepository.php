<?php

namespace App\Repository;

use App\Constant\Fonction;
use App\Entity\Utilisateur;
use App\Enum\MotifConservationUtilisateurEnum;
use App\Enum\ResultatSuppressionUtilisateurEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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
     * Persiste le mot de passe rehashé (migration MD5 legacy → bcrypt/argon2).
     *
     * Appelée explicitement par FormLoginAuthenticator::checkCredentials() après une
     * connexion réussie via l'encodeur legacy, puisque le CustomCredentials utilisé ne
     * déclenche pas le mécanisme standard PasswordUpgradeBadge de Symfony.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->_em->flush();
    }

    /**
     * Renvoie les utilisateurs actifs dont la dernière action date de moins d'un jour.
     *
     * Limité à 1000 utilisateurs maximum, en cohérence avec le legacy.
     * Comportement métier : évaluation d'un échantillon d'utilisateurs plutôt que la totalité.
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
            ->setMaxResults(1000)
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
            ->setParameter('fonction', Fonction::PREVENTIONNISTE)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC');
    }

    /**
     * Retourne les préventionnistes conseillés à partir de la dernière fiche du père.
     *
     * @return array<int, array{uid: mixed, nom: mixed, prenom: mixed}>
     */
    public function findPreventionnistesSuggeresPourEtablissement(int $idEtablissement): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT u.ID_UTILISATEUR AS uid,'
            .' ui.NOM_UTILISATEURINFORMATIONS AS nom,'
            .' ui.PRENOM_UTILISATEURINFORMATIONS AS prenom'
            .' FROM etablissement e'
            .' INNER JOIN etablissementinformations ei'
            .' ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT'
            .' AND ei.DATE_ETABLISSEMENTINFORMATIONS = ('
            .' SELECT MAX(ei_max.DATE_ETABLISSEMENTINFORMATIONS)'
            .' FROM etablissementinformations ei_max'
            .' WHERE ei_max.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT'
            .' )'
            .' INNER JOIN etablissementinformationspreventionniste eip'
            .' ON eip.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS'
            .' INNER JOIN utilisateur u ON u.ID_UTILISATEUR = eip.ID_UTILISATEUR'
            .' INNER JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS'
            .' INNER JOIN fonction f ON f.ID_FONCTION = ui.ID_FONCTION'
            .' WHERE e.ID_ETABLISSEMENT = ?'
            .' AND u.ACTIF_UTILISATEUR = 1'
            .' GROUP BY u.ID_UTILISATEUR, ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS'
            .' ORDER BY ui.NOM_UTILISATEURINFORMATIONS ASC, ui.PRENOM_UTILISATEURINFORMATIONS ASC',
            [$idEtablissement]
        )->fetchAllAssociative();
    }

    /**
     * Retourne les préventionnistes des groupements couvrant une commune, sans doublon.
     *
     * @return array<int, array{uid: mixed, nom: mixed, prenom: mixed}>
     */
    public function findPreventionnistesSuggeresPourCommune(string $numinsee): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT u.ID_UTILISATEUR AS uid,'
            .' ui.NOM_UTILISATEURINFORMATIONS AS nom,'
            .' ui.PRENOM_UTILISATEURINFORMATIONS AS prenom'
            .' FROM utilisateur u'
            .' INNER JOIN groupementpreventionniste gp ON gp.ID_UTILISATEUR = u.ID_UTILISATEUR'
            .' INNER JOIN groupement g ON g.ID_GROUPEMENT = gp.ID_GROUPEMENT'
            .' INNER JOIN groupementtype gt ON gt.ID_GROUPEMENTTYPE = g.ID_GROUPEMENTTYPE'
            .' INNER JOIN groupementcommune gc ON gc.ID_GROUPEMENT = g.ID_GROUPEMENT'
            .' INNER JOIN utilisateurinformations ui ON ui.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS'
            .' WHERE gc.NUMINSEE_COMMUNE = ?'
            .' ORDER BY gt.ID_GROUPEMENTTYPE ASC, g.LIBELLE_GROUPEMENT ASC,'
            .' ui.NOM_UTILISATEURINFORMATIONS ASC, ui.PRENOM_UTILISATEURINFORMATIONS ASC',
            [$numinsee]
        )->fetchAllAssociative();

        $preventionnistes = [];
        $utilisateursDejaAjoutes = [];
        foreach ($rows as $row) {
            $uid = (string) $row['uid'];
            if (isset($utilisateursDejaAjoutes[$uid])) {
                continue;
            }

            $utilisateursDejaAjoutes[$uid] = true;
            $preventionnistes[] = $row;
        }

        return $preventionnistes;
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
            ->setParameter('fonction', Fonction::PREVENTIONNISTE)
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

    /**
     * @return list<int>
     */
    public function findIdsByGroupe(int $groupeId): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT ID_UTILISATEUR FROM utilisateur WHERE ID_GROUPE = ? ORDER BY ID_UTILISATEUR',
            [$groupeId],
        );

        return array_map(intval(...), $ids);
    }

    /**
     * Références métier empêchant la suppression définitive des utilisateurs donnés (RG-USR-028).
     *
     * @param list<int> $ids
     *
     * @return array<int, list<MotifConservationUtilisateurEnum>> motifs indexés par identifiant d'utilisateur
     */
    public function findReferencesBloquantes(array $ids): array
    {
        $requetes = [
            MotifConservationUtilisateurEnum::CreateurDossier->value => 'SELECT DISTINCT CREATEUR_DOSSIER FROM dossier WHERE CREATEUR_DOSSIER IN (:ids)',
            MotifConservationUtilisateurEnum::PreventionnisteDossier->value => 'SELECT DISTINCT ID_PREVENTIONNISTE FROM dossierpreventionniste WHERE ID_PREVENTIONNISTE IN (:ids)',
            MotifConservationUtilisateurEnum::PreventionnisteEtablissement->value => 'SELECT DISTINCT ID_UTILISATEUR FROM etablissementinformationspreventionniste WHERE ID_UTILISATEUR IN (:ids)',
            MotifConservationUtilisateurEnum::PreventionnisteGroupement->value => 'SELECT DISTINCT ID_UTILISATEUR FROM groupementpreventionniste WHERE ID_UTILISATEUR IN (:ids)',
            MotifConservationUtilisateurEnum::AuteurHistoriqueEtablissement->value => 'SELECT DISTINCT UTILISATEUR_ETABLISSEMENTINFORMATIONS FROM etablissementinformations WHERE UTILISATEUR_ETABLISSEMENTINFORMATIONS IN (:ids)',
            MotifConservationUtilisateurEnum::AuteurSuppressionDossier->value => 'SELECT DISTINCT DELETED_BY FROM dossier WHERE DELETED_BY IN (:ids)',
            MotifConservationUtilisateurEnum::AuteurSuppressionEtablissement->value => 'SELECT DISTINCT DELETED_BY FROM etablissement WHERE DELETED_BY IN (:ids)',
            MotifConservationUtilisateurEnum::AuteurActualite->value => 'SELECT DISTINCT ID_UTILISATEUR FROM news WHERE ID_UTILISATEUR IN (:ids)',
        ];

        $connection = $this->getEntityManager()->getConnection();
        $references = [];

        foreach (array_chunk($ids, 500) as $lot) {
            foreach ($requetes as $motif => $sql) {
                /** @var list<int|string> $idsReferences */
                $idsReferences = $connection->fetchFirstColumn($sql, ['ids' => $lot], ['ids' => ArrayParameterType::INTEGER]);

                foreach ($idsReferences as $id) {
                    $references[(int) $id][] = MotifConservationUtilisateurEnum::from($motif);
                }
            }
        }

        ksort($references);

        return $references;
    }

    /**
     * Supprime définitivement l'utilisateur, ses préférences et ses rattachements, dans une
     * transaction dédiée (RG-USR-029, RG-USR-030). Sa fiche d'informations personnelles est
     * supprimée, sauf si elle est partagée (fiche mairie d'une commune, contact, autre compte) :
     * elle est alors conservée intacte.
     */
    public function supprimerDefinitivement(int $id, int $groupeId): ResultatSuppressionUtilisateurEnum
    {
        return $this->getEntityManager()->getConnection()->transactional(static function (Connection $connection) use ($id, $groupeId): ResultatSuppressionUtilisateurEnum {
            $informationsId = $connection->fetchOne(
                'SELECT ID_UTILISATEURINFORMATIONS FROM utilisateur WHERE ID_UTILISATEUR = ? AND ID_GROUPE = ? FOR UPDATE',
                [$id, $groupeId],
            );

            if (false === $informationsId) {
                return ResultatSuppressionUtilisateurEnum::Absent;
            }

            $connection->executeStatement('DELETE FROM utilisateurpreferences WHERE ID_UTILISATEUR = ?', [$id]);
            $connection->executeStatement('DELETE FROM utilisateurcommission WHERE ID_UTILISATEUR = ?', [$id]);
            $connection->executeStatement('DELETE FROM utilisateurgroupement WHERE ID_UTILISATEUR = ?', [$id]);
            $connection->executeStatement('DELETE FROM utilisateur WHERE ID_UTILISATEUR = ?', [$id]);

            $informationsPartagees = (bool) $connection->fetchOne(
                <<<'SQL'
                    SELECT EXISTS (SELECT 1 FROM adressecommune WHERE ID_UTILISATEURINFORMATIONS = :informations)
                        OR EXISTS (SELECT 1 FROM commissioncontact WHERE ID_UTILISATEURINFORMATIONS = :informations)
                        OR EXISTS (SELECT 1 FROM dossiercontact WHERE ID_UTILISATEURINFORMATIONS = :informations)
                        OR EXISTS (SELECT 1 FROM etablissementcontact WHERE ID_UTILISATEURINFORMATIONS = :informations)
                        OR EXISTS (SELECT 1 FROM groupementcontact WHERE ID_UTILISATEURINFORMATIONS = :informations)
                        OR EXISTS (SELECT 1 FROM groupement WHERE ID_UTILISATEURINFORMATIONS = :informations)
                        OR EXISTS (SELECT 1 FROM utilisateur WHERE ID_UTILISATEURINFORMATIONS = :informations)
                    SQL,
                ['informations' => $informationsId],
            );

            if ($informationsPartagees) {
                return ResultatSuppressionUtilisateurEnum::SupprimeFicheConservee;
            }

            $connection->executeStatement('DELETE FROM utilisateurinformations WHERE ID_UTILISATEURINFORMATIONS = ?', [$informationsId]);

            return ResultatSuppressionUtilisateurEnum::Supprime;
        });
    }
}
