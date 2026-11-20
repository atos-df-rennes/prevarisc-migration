<?php

declare(strict_types=1);

namespace App\Platau;

final readonly class HttpConsultationReader extends PlatauApiReader implements ConsultationReader
{
    public function details(string $id): array
    {
        $page = 0;
        do {
            $data = $this->read('POST', 'consultations/recherche', [
                'query' => ['colonneTri' => 'DT_LIMITE_DE_REPONSE', 'sensTri' => 'DESC', 'numeroPage' => $page, 'nbElementsParPage' => 10],
                'json' => ['criteresSurConsultations' => ['idConsultation' => $id]],
            ]);
            $pages = $data['nombrePages'] ?? null;
            $results = $data['resultats'] ?? null;
            if (!is_int($pages) || $pages < 0 || !is_array($results) || !array_is_list($results)) {
                throw new PlatauException("La pagination renvoyée par Plat'AU est incorrecte.");
            }

            if ([] !== $results) {
                $row = $results[0];
                if (!is_array($row) || !is_array($row['dossier'] ?? null)
                    || !is_array($row['dossier']['consultations'] ?? null) || 1 !== count($row['dossier']['consultations'])) {
                    throw new PlatauException("Réponse Plat'AU incorrecte : une consultation par dossier est attendue.");
                }

                return $row;
            }
        } while (++$page < $pages);

        throw new PlatauException(sprintf('la consultation %s est introuvable selon les critères de recherche', $id));
    }
}
