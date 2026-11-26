<?php

declare(strict_types=1);

namespace App\Platau;

use App\Constant\DossierType;
use App\Enum\PlatauStatutPecEnum;
use App\Platau\Dto\Dossier;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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

    public function import(Dossier $dossier, ?string $serviceConsultant, ?string $serviceInstructeur): int
    {
        return $this->db->transactional(function (Connection $db) use ($dossier, $serviceConsultant, $serviceInstructeur): int {
            $consultation = $dossier->consultation;
            $now = new \DateTimeImmutable()->format('Y-m-d H:i:s');
            $emission = $consultation->dtEmission;
            $decision = $consultation->dtConsultation;

            $db->insert('platauconsultation', [
                'ID_PLATAU' => $consultation->idConsultation,
                'DATE_REPONSE_ATTENDUE' => $consultation->dateReponseAttendue(),
            ]);
            $db->insert('dossier', [
                'TYPE_DOSSIER' => DossierType::ETUDE,
                'INCOMPLET_DOSSIER' => null,
                'CREATEUR_DOSSIER' => $this->userId,
                'DEMANDEUR_DOSSIER' => $dossier->demandeurs(),
                'TYPESERVINSTRUC_DOSSIER' => 'servInstGrp',
                'SERVICEINSTRUC_DOSSIER' => $serviceInstructeur,
                'DATESDIS_DOSSIER' => $now,
                'DATE_NOTIFICATION' => $now,
                'DATEINSERT_DOSSIER' => new \DateTimeImmutable($emission ?? $decision ?? 'now')->format('Y-m-d H:i:s'),
                'ID_PLATAU' => $consultation->idConsultation,
                'OBJET_DOSSIER' => sprintf('Objet de la consultation : %s ; %s',
                    $consultation->txObjetDeLaConsultation ?? 'SANS OBJET',
                    $dossier->txDescriptifGlobal ?? 'SANS DESCRIPTIF GLOBAL',
                ),
                'OBSERVATION_DOSSIER' => sprintf(
                    'Consultation PLATAU : Consultation de type %s décidée le %s et transmise au service consultable le %s. Une réponse est attendue dans %s %s. (ID PLATAU DOSSIER : %s / Service consultant : %s)',
                    $consultation->nomTypeConsultation->libNom ?? 'INCONNUE',
                    $decision ?? 'DATE CONSULTATION INCONNUE',
                    $emission ?? 'DATE EMISSION INCONNUE',
                    $consultation->delaiDeReponse ?? 'DELAI INCONNU',
                    $consultation->nomTypeDelai->libNom ?? 'TYPE DELAI INCONNU',
                    $dossier->idDossier ?? 'ID INCONNU',
                    $serviceConsultant ?? 'SERVICE CONSULTANT INCONNU',
                ),
            ]);
            $dossierId = $db->lastInsertId();
            if (null !== $dossier->noLocal) {
                $db->insert('dossierdocurba', [
                    'ID_DOSSIER' => $dossierId,
                    'NUM_DOCURBA' => $dossier->noLocal.($dossier->suffixeNoLocal ?? ''),
                ]);
            }

            $db->insert('dossiernature', [
                'ID_DOSSIER' => $dossierId,
                'ID_NATURE' => match ($dossier->nomTypeDossier?->idNom) {
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

            return (int) $dossierId;
        });
    }

    public function markAwaiting(string $id): void
    {
        $this->db->update('platauconsultation', ['STATUT_PEC' => PlatauStatutPecEnum::EN_ATTENTE], ['ID_PLATAU' => $id]);
    }
}
