<?php

declare(strict_types=1);

namespace App\Platau;

final readonly class HttpPieceReader extends PlatauApiReader
{
    /** @return list<mixed> */
    public function pieces(string $dossier): array
    {
        $pieces = $this->read('GET', 'dossiers/'.rawurlencode($dossier).'/pieces', []);
        if (!array_is_list($pieces)) {
            throw new ReceptionException('Liste des pièces incorrecte.');
        }

        return $pieces;
    }

    /** @return iterable<string> */
    public function consultationIds(string $id): iterable
    {
        $page = 0;
        do {
            $data = $this->read('POST', 'consultations/recherche', [
                'query' => ['colonneTri' => 'DT_LIMITE_DE_REPONSE', 'sensTri' => 'DESC', 'numeroPage' => $page, 'nbElementsParPage' => 10],
                'json' => ['criteresSurDossiers' => ['idDossier' => $id]],
            ]);
            $pages = $data['nombrePages'] ?? null;
            $results = $data['resultats'] ?? null;
            if (!empty($data['erreur']) || !is_int($pages) || $pages < 0 || !is_array($results) || !array_is_list($results)) {
                throw new ReceptionException('Recherche des consultations incorrecte.');
            }

            foreach ($results as $result) {
                $dossier = is_array($result) ? ($result['dossier'] ?? null) : null;
                if (!is_array($dossier) || ($dossier['idDossier'] ?? null) !== $id) {
                    throw new ReceptionException('Dossier de consultation incorrect.');
                }
                $consultations = $dossier['consultations'] ?? null;
                if (!is_array($consultations) || !array_is_list($consultations)) {
                    throw new ReceptionException('Liste de consultations incorrecte.');
                }

                foreach ($consultations as $consultation) {
                    $consultationId = is_array($consultation) ? ($consultation['idConsultation'] ?? null) : null;
                    if (!is_string($consultationId) || '' === trim($consultationId)) {
                        throw new ReceptionException('Identifiant de consultation incorrect.');
                    }

                    yield $consultationId;
                }
            }
        } while (++$page < $pages);
    }
}
