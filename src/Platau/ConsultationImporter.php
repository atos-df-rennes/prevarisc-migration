<?php

declare(strict_types=1);

namespace App\Platau;

use App\Constant\DossierType;
use App\Enum\PlatauStatutPecEnum;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @phpstan-type Personne array{roles?: array<array{nomRole: array{idNom: int}}>|null, noms?: list<string>|null, prenoms?: list<string>|null, libDenomination?: string|null, libRaisonSociale?: string|null}
 * @phpstan-type Consultation array{idConsultation: string, dtEmission?: string|null, dtConsultation?: string|null, txObjetDeLaConsultation?: string|null, delaiDeReponse?: int|null, nomTypeDelai?: array{libNom?: string|null}, nomTypeConsultation?: array{libNom?: string|null}}
 * @phpstan-type Dossier array{consultations: array<Consultation>, nomTypeDossier: array{idNom: int}, personnes?: array<Personne>|null, idDossier?: string|null, noLocal?: string|null, suffixeNoLocal?: string|null, txDescriptifGlobal?: string|null}
 */
final readonly class ConsultationImporter
{
    public function __construct(
        private Connection $db,
        #[Autowire(env: 'PREVARISC_DB_PLATAU_USER_ID')] private string $userId,
    ) {
    }

    public function requireCreator(): void
    {
        if (false === filter_var($this->userId, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new PlatauException('PREVARISC_DB_PLATAU_USER_ID doit désigner un utilisateur Prevarisc.');
        }

        if (false === $this->db->fetchOne('SELECT 1 FROM utilisateur WHERE ID_UTILISATEUR = ?', [$this->userId])) {
            throw new PlatauException('Utilisateur Prevarisc introuvable : vérifier PREVARISC_DB_PLATAU_USER_ID.');
        }
    }

    public function exists(string $id): bool
    {
        return false !== $this->db->fetchOne('SELECT 1 FROM dossier WHERE ID_PLATAU = ? LIMIT 1', [$id]);
    }

    /** @param array<array-key, mixed> $information Élément brut renvoyé par ConsultationReader::details(). */
    public function import(array $information, ?string $serviceConsultant, ?string $serviceInstructeur): void
    {
        /** @var Dossier|null $dossier */
        $dossier = $information['dossier'] ?? null;
        $consultations = $dossier['consultations'] ?? [];
        if (null === $dossier || 1 !== count($consultations)) {
            throw new PlatauException('Une consultation par dossier est attendue.');
        }

        $consultation = reset($consultations);
        if (!isset($consultation['idConsultation']) || '' === $consultation['idConsultation']) {
            throw new PlatauException('Identifiant de consultation manquant.');
        }

        $this->db->transactional(function (Connection $db) use ($dossier, $consultation, $serviceConsultant, $serviceInstructeur): void {
            $now = new \DateTimeImmutable()->format('Y-m-d H:i:s');
            $emission = $consultation['dtEmission'] ?? null;
            $decision = $consultation['dtConsultation'] ?? null;
            $delai = $consultation['delaiDeReponse'] ?? null;
            $unite = $consultation['nomTypeDelai']['libNom'] ?? null;

            $db->insert('platauconsultation', [
                'ID_PLATAU' => $consultation['idConsultation'],
                'DATE_REPONSE_ATTENDUE' => self::dateReponse($emission, $delai, $unite),
            ]);
            $db->insert('dossier', [
                'TYPE_DOSSIER' => DossierType::ETUDE,
                'INCOMPLET_DOSSIER' => null,
                'CREATEUR_DOSSIER' => $this->userId,
                'DEMANDEUR_DOSSIER' => self::demandeurs($dossier['personnes'] ?? []),
                'TYPESERVINSTRUC_DOSSIER' => 'servInstGrp',
                'SERVICEINSTRUC_DOSSIER' => $serviceInstructeur,
                'DATESDIS_DOSSIER' => $now,
                'DATE_NOTIFICATION' => $now,
                'DATEINSERT_DOSSIER' => new \DateTimeImmutable($emission ?? $decision ?? 'now')->format('Y-m-d H:i:s'),
                'ID_PLATAU' => $consultation['idConsultation'],
                'OBJET_DOSSIER' => sprintf('Objet de la consultation : %s ; %s',
                    $consultation['txObjetDeLaConsultation'] ?? 'SANS OBJET',
                    $dossier['txDescriptifGlobal'] ?? 'SANS DESCRIPTIF GLOBAL',
                ),
                'OBSERVATION_DOSSIER' => sprintf(
                    'Consultation PLATAU : Consultation de type %s décidée le %s et transmise au service consultable le %s. Une réponse est attendue dans %s %s. (ID PLATAU DOSSIER : %s / Service consultant : %s)',
                    $consultation['nomTypeConsultation']['libNom'] ?? 'INCONNUE',
                    $decision ?? 'DATE CONSULTATION INCONNUE',
                    $emission ?? 'DATE EMISSION INCONNUE',
                    $delai ?? 'DELAI INCONNU',
                    $unite ?? 'TYPE DELAI INCONNU',
                    $dossier['idDossier'] ?? 'ID INCONNU',
                    $serviceConsultant ?? 'SERVICE CONSULTANT INCONNU',
                ),
            ]);
            $dossierId = $db->lastInsertId();
            if (isset($dossier['noLocal'])) {
                $db->insert('dossierdocurba', [
                    'ID_DOSSIER' => $dossierId,
                    'NUM_DOCURBA' => $dossier['noLocal'].($dossier['suffixeNoLocal'] ?? ''),
                ]);
            }

            $db->insert('dossiernature', [
                'ID_DOSSIER' => $dossierId,
                'ID_NATURE' => match ($dossier['nomTypeDossier']['idNom'] ?? null) {
                    1, 2 => 62,
                    3 => 30,
                    4 => 1,
                    5 => 14,
                    6 => 15,
                    7 => throw new PlatauException('Nature Demande de transfert (DT) non supportée'),
                    8 => throw new PlatauException('Nature Dossier d’infraction (DI) non supportée'),
                    default => throw new PlatauException('Nature inconnue'),
                },
            ]);
        });
    }

    public function markAwaiting(string $id): void
    {
        $this->db->update('platauconsultation', ['STATUT_PEC' => PlatauStatutPecEnum::EN_ATTENTE], ['ID_PLATAU' => $id]);
    }

    /** @param array<Personne> $personnes */
    private static function demandeurs(array $personnes): ?string
    {
        $demandeurs = [];
        foreach ($personnes as $personne) {
            foreach ($personne['roles'] ?? [] as $role) {
                if (1 !== $role['nomRole']['idNom']) {
                    continue;
                }

                if (!isset($personne['noms']) && !isset($personne['prenoms'])) {
                    $demandeurs[] = $personne['libDenomination'] ?? $personne['libRaisonSociale'] ?? 'Inconnu';
                } else {
                    $demandeurs[] = implode(' ', array_map(trim(...), [...($personne['noms'] ?? []), ...($personne['prenoms'] ?? [])]));
                }

                break;
            }
        }

        return [] === $demandeurs ? null : implode(' / ', $demandeurs);
    }

    private static function dateReponse(?string $emission, ?int $delai, ?string $unite): ?string
    {
        if (null === $emission || null === $delai) {
            return null;
        }

        $interval = match ($unite) {
            'Jours calendaires' => new \DateInterval("P{$delai}D"),
            'Mois' => new \DateInterval("P{$delai}M"),
            default => null,
        };
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $emission);

        return !$interval instanceof \DateInterval || false === $date ? null : $date->add($interval)->format('Y-m-d');
    }
}
