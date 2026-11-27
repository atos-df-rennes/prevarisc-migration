<?php

declare(strict_types=1);

namespace App\Platau;

abstract readonly class ExportConsultationsApi extends PlatauApiReader
{
    /**
     * @param list<int> $states
     *
     * @return iterable<array{id: string, state: int}>
     */
    public function candidates(?string $consultationId, array $states = [1, 2]): iterable
    {
        // Les écritures retirent des consultations des pages suivantes : finir la recherche avant le premier yield.
        $candidates = [];
        foreach ($this->search($consultationId, $states) as [, $consultation]) {
            $id = $consultation['idConsultation'];
            $state = $consultation['nomEtatConsultation'] ?? null;
            if (!is_array($state) || !is_int($state['idNom'] ?? null)) {
                throw new PlatauException("État de consultation Plat'AU incorrect.");
            }

            if (null !== $consultationId || in_array($state['idNom'], $states, true)) {
                $candidates[$id] = ['id' => $id, 'state' => $state['idNom']];
            }
        }

        yield from array_values($candidates);
    }

    /**
     * @param list<int> $states
     *
     * @return \Generator<int, array{array<array-key, mixed>, array{idConsultation: string, ...}}>
     */
    protected function search(?string $id, array $states = [1, 2]): \Generator
    {
        $criteria = null === $id ? ['nomEtatConsultation' => $states] : ['idConsultation' => $id];
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
}
