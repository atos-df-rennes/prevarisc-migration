<?php

declare(strict_types=1);

namespace App\Platau;

use App\Enum\PlatauStatutAvisEnum as Status;
use Symfony\Component\Clock\DatePoint;

final readonly class AvisApi extends ExportConsultationsApi
{
    public static function eligible(int $state, ?string $status): bool
    {
        return 3 === $state || (6 === $state && in_array($status, [Status::A_RENVOYER, Status::EN_ERREUR], true));
    }

    /**
     * @param list<string>           $prescriptions
     * @param array<string, ?string> $author
     *
     * @return array<string, mixed>
     */
    public function opinion(string $id, int $commission, array $prescriptions, array $author, ?string $status, ?string $localDate): array
    {
        $date = Status::A_RENVOYER === $status
            ? $this->checkedDate($localDate ?? $this->previousDate($id))
            : new DatePoint()->format('Y-m-d');

        return [
            'idConsultation' => $id,
            'boEstTacite' => false,
            'nomTypeAvis' => 1,
            'nomNatureAvisRendu' => match ($commission) {
                1 => [] === $prescriptions ? 1 : 2,
                2 => 3,
                6 => 6,
                default => throw new \InvalidArgumentException('Avis de commission non transmissible.'),
            },
            'txAvis' => 'Avis Prevarisc. Prescriptions données : '.([] === $prescriptions ? 'RAS' : implode(', ', $prescriptions)),
            'dtAvis' => $date,
            'idActeurAuteur' => $this->actorId,
        ] + array_filter($author, static fn (?string $value): bool => null !== $value);
    }

    /** @return array{idDossier: string, noVersion: int}|null Null si l'état éligible observé à la découverte a changé. */
    public function refreshTarget(string $id, int $expectedState): ?array
    {
        [$dossier, $consultation] = $this->search($id)->current();
        $state = $consultation['nomEtatConsultation'] ?? null;
        if (!is_array($state) || !is_int($state['idNom'] ?? null)) {
            throw new PlatauException("État de consultation Plat'AU incorrect.");
        }
        if ($state['idNom'] !== $expectedState) {
            return null;
        }
        $dossierId = $dossier['idDossier'] ?? null;
        if (!is_string($dossierId) || '' === trim($dossierId) || !is_int($dossier['noVersion'] ?? null)) {
            throw new PlatauException("Identifiant du dossier ou version Plat'AU incorrects.");
        }

        return ['idDossier' => $dossierId, 'noVersion' => $dossier['noVersion']];
    }

    /**
     * @param array{idDossier: string, noVersion: int} $target
     * @param array<string, mixed>                     $opinion
     * @param array<int, array<string, mixed>>         $documents
     *
     * @return list<mixed>
     */
    public function send(array $target, array $opinion, array $documents): array
    {
        $opinion['documents'] = array_values($documents);
        $rows = $this->postOnce('avis', [$target + ['avis' => [$opinion]]]);
        $row = array_is_list($rows) && 1 === count($rows) ? $rows[0] : null;
        if (!is_array($row) || [] !== ($row['erreurs'] ?? [])
            || (array_key_exists('idDossier', $row) && $row['idDossier'] !== $target['idDossier'])) {
            throw new PlatauException("Dépôt d'avis non confirmé par Plat'AU.");
        }
        $items = $row['avis'] ?? null;
        $item = is_array($items) && array_is_list($items) && 1 === count($items) ? $items[0] : null;
        if (!is_array($item) || !is_string($item['idAvis'] ?? null) || '' === trim($item['idAvis'])) {
            throw new PlatauException("Identifiant d'avis absent ou incorrect dans la réponse de Plat'AU.");
        }
        $remote = $item['documents'] ?? null;

        return is_array($remote) && array_is_list($remote) ? $remote : [];
    }

    private function previousDate(string $id): string
    {
        $date = null;
        $page = 0;
        do {
            $data = $this->read('POST', 'avis/recherche', [
                'query' => ['numeroPage' => $page, 'nbElementsParPage' => 100],
                'json' => ['criteresSurConsultations' => ['idConsultation' => $id]],
            ]);
            $pages = $data['nombrePages'] ?? null;
            $rows = $data['resultats'] ?? null;
            if (!empty($data['erreur']) || !is_int($pages) || $pages < 0 || !is_array($rows) || !array_is_list($rows)) {
                throw new PlatauException("Recherche des avis Plat'AU incorrecte.");
            }
            foreach ($rows as $row) {
                if (!is_array($row) || !empty($row['erreur']) || !is_array($row['dossier'] ?? null)) {
                    throw new PlatauException("Résultat de recherche d'avis Plat'AU incorrect.");
                }
                $items = $row['dossier']['avis'] ?? null;
                if (!is_array($items) || !array_is_list($items)) {
                    throw new PlatauException("Résultat de recherche d'avis Plat'AU incorrect.");
                }
                foreach ($items as $item) {
                    if (is_array($item) && ($item['idConsultation'] ?? null) === $id) {
                        // Comme la passerelle, conserver le dernier avis de la consultation.
                        $date = $item['dtAvis'] ?? null;
                    }
                }
            }
        } while (++$page < $pages);
        if (!is_string($date)) {
            throw new PlatauException(sprintf("Date d'avis introuvable pour la consultation %s.", $id));
        }

        return $date;
    }

    private function checkedDate(string $date): string
    {
        if (1 !== preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new PlatauException("Date d'avis incorrecte.");
        }

        return $date;
    }
}
