<?php

namespace App\Service\Alerte;

use App\Entity\Etablissement;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des alertes par email.
 *
 * Équivalent legacy : Service_Alerte + Model_DbTable_Utilisateur::findUtilisateursForAlerte()
 */
class AlerteService
{
    /**
     * Type de changement pour statut.
     */
    public const TYPE_CHANGEMENT_STATUT = 1;

    /**
     * Type de changement pour avis de commission.
     */
    public const TYPE_CHANGEMENT_AVIS = 2;

    /**
     * Type de changement pour classement.
     */
    public const TYPE_CHANGEMENT_CLASSEMENT = 3;

    /**
     * Mapping type changement → nom privilege.
     */
    private const PRIVILEGES_CHANGEMENT = [
        self::TYPE_CHANGEMENT_STATUT => 'alerte_statut',
        self::TYPE_CHANGEMENT_AVIS => 'alerte_avis',
        self::TYPE_CHANGEMENT_CLASSEMENT => 'alerte_classement',
    ];

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Génère le lien HTML "Alerter" pour les flashMessages.
     *
     * Équivalent legacy : Service_Alerte::getLink()
     *
     * @param int      $idTypeChangement Type de changement (2 = avis)
     * @param int|null $idEtablissement  ID établissement concerné
     *
     * @return string Lien HTML avec data-attributes pour le JavaScript
     */
    public function getLink(int $idTypeChangement, ?int $idEtablissement = null): string
    {
        $etabData = '';
        if (null !== $idEtablissement) {
            $etabData = sprintf('data-ets="%d"', $idEtablissement);
        }

        return sprintf(
            '<a data-value="%d" %s class="alerte-link"><span class="glyphicon glyphicon-bell" aria-hidden="true"></span>Alerter</a>',
            $idTypeChangement,
            $etabData
        );
    }

    /**
     * Récupère les utilisateurs destinataires pour une alerte.
     *
     * Équivalent legacy : Model_DbTable_Utilisateur::findUtilisateursForAlerte()
     *
     * Logique :
     * - Utilisateurs avec le privilege correspondant au type de changement
     * - Ayant une adresse email valide
     * - Liés à la commune de l'établissement (directement OU via groupement)
     *
     * @param int           $typeChangement Type de changement (1=statut, 2=avis, 3=classement)
     * @param Etablissement $etablissement  Établissement concerné
     *
     * @return array<int, array{id: int, nom: string, prenom: string, email: string}> Liste utilisateurs
     */
    public function getDestinataires(int $typeChangement, Etablissement $etablissement): array
    {
        // 1. Récupérer nom du privilege
        $privilegeName = self::PRIVILEGES_CHANGEMENT[$typeChangement] ?? 'alerte_statut';

        // 2. Récupérer NUMINSEE depuis première adresse établissement
        $numInsee = $this->getNumInseeFromEtablissement($etablissement);

        if (null === $numInsee) {
            return []; // Pas de commune = pas de destinataires
        }

        // 3. Query Doctrine avec LEFT JOIN pour groupement
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('DISTINCT u.id as id', 'ui.nom as nom', 'ui.prenom as prenom', 'ui.mail as mail')
            ->from('App\Entity\Utilisateur', 'u')
            ->join('u.informations', 'ui')
            ->join('u.groupe', 'g')
            ->join('g.privileges', 'p') // p est déjà Privileges, pas besoin de join supplémentaire
            ->leftJoin('u.groupements', 'ug')
            ->leftJoin('ug.communes', 'gc')
            ->where('ui.mail IS NOT NULL')
            ->andWhere('ui.mail != :emptyEmail')
            ->andWhere('p.name = :privilegeName')
            ->andWhere($qb->expr()->orX(
                'u.commune = :numInsee', // Commune est un ManyToOne, on compare l'objet
                'gc.id = :numInsee'     // gc.id est le NUMINSEE_COMMUNE
            ))
            ->setParameter('emptyEmail', '')
            ->setParameter('privilegeName', $privilegeName)
            ->setParameter('numInsee', $numInsee)
            ->groupBy('u.id')
        ;

        // Exécuter la requête
        /** @var array<int, array{id: int, nom: string, prenom: string, email: string}> $results */
        $results = $qb->getQuery()->getResult();

        return $results;
    }

    /**
     * Extrait le NUMINSEE depuis la première adresse de l'établissement.
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return string|null NUMINSEE ou null si aucune adresse
     */
    private function getNumInseeFromEtablissement(Etablissement $etablissement): ?string
    {
        $adresses = $etablissement->getAdresses();

        if ($adresses->isEmpty()) {
            return null;
        }

        /** @var \App\Entity\EtablissementAdresse|false $premiereAdresse */
        $premiereAdresse = $adresses->first();
        if (false === $premiereAdresse) {
            return null;
        }

        $commune = $premiereAdresse->getCommune();

        return $commune ? $commune->getId() : null;
    }
}
