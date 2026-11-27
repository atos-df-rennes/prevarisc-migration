<?php

declare(strict_types=1);

namespace App\Platau;

use Symfony\Component\Clock\DatePoint;

/**
 * @phpstan-type PecPayload array{0: array{idDossier: string, noVersion: int, consultations: array{0: array{idConsultation: string, noVersion: int, pecMetier: array<string, mixed>}}}}
 */
final readonly class PecApi extends PlatauApiReader
{
    /** @return iterable<array{id: string, state: int}> */
    public function candidates(?string $consultationId): iterable
    {
        // Les écritures retirent des consultations des pages suivantes : finir la recherche avant le premier yield.
        $candidates = [];
        foreach ($this->search($consultationId) as [, $consultation]) {
            $id = $consultation['idConsultation'];
            $state = $consultation['nomEtatConsultation'] ?? null;
            if (!is_array($state) || !is_int($state['idNom'] ?? null)) {
                throw new PlatauException("État de consultation Plat'AU incorrect.");
            }

            if (null !== $consultationId || in_array($state['idNom'], [1, 2], true)) {
                $candidates[$id] = ['id' => $id, 'state' => $state['idNom']];
            }
        }

        foreach ($candidates as $candidate) {
            yield $candidate;
        }
    }

    /**
     * @param array{prenomAuteur: ?string, nomAuteur: ?string, emailAuteur: ?string, telephoneAuteur: ?string} $author
     *
     * @return PecPayload
     */
    public function payload(string $id, bool $complete, ?string $observations, array $author, ?string $previousDate, ?int $delay): array
    {
        [$dossier, $consultation] = $this->search($id)->current();
        if (!is_string($dossier['idDossier'] ?? null) || '' === trim($dossier['idDossier'])
            || !is_int($dossier['noVersion'] ?? null) || !is_int($consultation['noVersion'] ?? null)) {
            throw new PlatauException("Identifiant du dossier ou versions Plat'AU incorrects.");
        }

        $date = $this->pecDate($previousDate);
        $pec = [
            'dtPecMetier' => $date->format('Y-m-d'),
            'dtLimiteReponse' => $this->deadline($date, $consultation, $delay),
            'nomStatutPecMetier' => $complete ? 1 : 2,
            'idActeurEmetteur' => $this->actorId,
            'txObservations' => (string) $observations,
            'documents' => [],
        ];
        foreach (['prenomAuteur', 'nomAuteur', 'emailAuteur', 'telephoneAuteur'] as $key) {
            if (null !== $author[$key]) {
                $pec[$key] = $author[$key];
            }
        }

        return [[
            'idDossier' => $dossier['idDossier'],
            'noVersion' => $dossier['noVersion'],
            'consultations' => [['idConsultation' => $id, 'noVersion' => $consultation['noVersion'], 'pecMetier' => $pec]],
        ]];
    }

    /**
     * @param PecPayload                             $payload
     * @param array<array-key, array<string, mixed>> $documents
     *
     * @return list<array<array-key, mixed>> Documents dans l'ordre du dépôt ; une position non identifiée reste vide.
     */
    public function send(array $payload, array $documents): array
    {
        $payload[0]['consultations'][0]['pecMetier']['documents'] = array_values($documents);
        $rows = $this->postOnce('pecMetier/consultations', $payload);
        $row = array_is_list($rows) && 1 === count($rows) ? $rows[0] : null;
        if (!is_array($row) || [] !== ($row['erreurs'] ?? [])
            || (array_key_exists('idDossier', $row) && $row['idDossier'] !== $payload[0]['idDossier'])) {
            throw new PlatauException("Dépôt de PEC non confirmé par Plat'AU.");
        }
        $consultations = $row['consultations'] ?? null;
        $item = is_array($consultations) && array_is_list($consultations) && 1 === count($consultations) ? $consultations[0] : null;
        $id = $payload[0]['consultations'][0]['idConsultation'];
        if (!is_array($item) || (array_key_exists('idConsultation', $item) && $item['idConsultation'] !== $id)
            || !is_array($item['pecMetier'] ?? null) || ([] !== $item['pecMetier'] && array_is_list($item['pecMetier']))) {
            throw new PlatauException("Consultation PEC absente ou incorrecte dans la réponse de Plat'AU.");
        }
        $pec = $item['pecMetier'];
        $remote = $pec['documents'] ?? null;
        if (!is_array($remote) || !array_is_list($remote) || count($documents) !== count($remote)) {
            // La PEC a été acceptée, mais aucun rapprochement positionnel n'est fiable.
            return [];
        }
        $result = [];
        foreach ($remote as $document) {
            $result[] = is_array($document) && is_string($document['idDocument'] ?? null) && '' !== trim($document['idDocument']) ? $document : [];
        }

        return $result;
    }

    /** @return \Generator<int, array{array<array-key, mixed>, array{idConsultation: string, ...}}> */
    private function search(?string $id): \Generator
    {
        $criteria = null === $id ? ['nomEtatConsultation' => [1, 2]] : ['idConsultation' => $id];
        $page = 0;
        do {
            $data = $this->read('POST', 'consultations/recherche', [
                'query' => ['numeroPage' => $page, 'nbElementsParPage' => 100, 'colonneTri' => 'DT_LIMITE_DE_REPONSE', 'sensTri' => 'DESC'],
                'json' => ['criteresSurConsultations' => $criteria],
            ]);
            $pages = $data['nombrePages'] ?? null;
            $rows = $data['resultats'] ?? null;
            if (!empty($data['erreur']) || !is_int($pages) || $pages < 0 || !is_array($rows) || !array_is_list($rows)) {
                throw new PlatauException("Pagination des consultations Plat'AU incorrecte.");
            }
            foreach ($rows as $row) {
                $dossier = is_array($row) ? ($row['dossier'] ?? null) : null;
                if (!is_array($dossier) || !is_array($dossier['consultations'] ?? null) || !array_is_list($dossier['consultations'])) {
                    throw new PlatauException("Liste des consultations Plat'AU incorrecte.");
                }
                foreach ($dossier['consultations'] as $consultation) {
                    if (!is_array($consultation) || !is_string($consultation['idConsultation'] ?? null) || '' === trim($consultation['idConsultation'])) {
                        throw new PlatauException("Identifiant de consultation Plat'AU incorrect.");
                    }
                    if (null === $id || $id === $consultation['idConsultation']) {
                        yield [$dossier, $consultation];
                        if (null !== $id) {
                            return;
                        }
                    }
                }
            }
        } while (++$page < $pages);
        if (null !== $id) {
            throw new PlatauException(sprintf('la consultation %s est introuvable selon les critères de recherche', $id));
        }
    }

    private function pecDate(?string $previousDate): \DateTimeImmutable
    {
        if (null === $previousDate) {
            return new DatePoint('today');
        }
        if (1 !== preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $previousDate, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new PlatauException('Date de PEC incorrecte.');
        }

        return new \DateTimeImmutable($previousDate);
    }

    /** @param array<array-key, mixed> $consultation */
    private function deadline(\DateTimeImmutable $date, array $consultation, ?int $delay): string
    {
        $unit = 'D';
        if (null === $delay) {
            $delay = $consultation['delaiDeReponse'] ?? null;
            $type = $consultation['nomTypeDelai'] ?? null;
            $unit = match (is_array($type) ? ($type['libNom'] ?? null) : null) {
                'Jours calendaires' => 'D',
                'Mois' => 'M',
                default => throw new PlatauException('Type de délai de réponse inconnu.'),
            };
        }
        // Même en jours, un délai supérieur ne tient pas dans une date v13 à quatre chiffres.
        if (!is_int($delay) || $delay < 0 || $delay > 10000 * 366) {
            throw new PlatauException('Délai de réponse incorrect.');
        }
        $deadline = $date->add(new \DateInterval('P'.$delay.$unit))->format('Y-m-d');
        if (10 !== strlen($deadline)) {
            throw new PlatauException('Date limite de réponse hors limites.');
        }

        return $deadline;
    }
}
