<?php

declare(strict_types=1);

namespace App\Platau;

use App\Platau\Dto\Dossier;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class ReceptionReader
{
    public function __construct(
        private ConsultationReader $consultations,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function dossier(string $id): Dossier
    {
        $information = $this->consultations->details($id);
        $dossier = $information['dossier'] ?? null;
        $consultations = is_array($dossier) ? ($dossier['consultations'] ?? null) : null;
        $consultation = is_array($consultations) && 1 === count($consultations) ? ($consultations[0] ?? null) : null;
        if (!is_array($dossier) || !is_array($consultation) || $id !== ($consultation['idConsultation'] ?? null)) {
            throw new ReceptionException('La consultation renvoyée ne correspond pas à la notification.');
        }

        $instructeur = $dossier['idServiceInstructeur'] ?? null;
        $consultant = $consultation['idServiceConsultant'] ?? null;
        if ((null !== $instructeur && !is_string($instructeur)) || (null !== $consultant && !is_string($consultant))) {
            throw new ReceptionException('Identifiant de service incorrect.');
        }

        try {
            return $this->denormalizer->denormalize($dossier, Dossier::class);
        } catch (ExceptionInterface) {
            // Une erreur de dénormalisation peut contenir des valeurs du dossier.
            throw new ReceptionException('Échec du traitement : import de la consultation.');
        }
    }
}
