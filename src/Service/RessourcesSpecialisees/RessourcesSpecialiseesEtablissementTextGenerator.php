<?php

namespace App\Service\RessourcesSpecialisees;

use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesBupTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesCampingTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesCelluleTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesErpTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesHabitationTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesIghTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesIopTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesManifestationTemporaireTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesZoneTexter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-type FormData array{
 *     text: string,
 *     genre: int,
 *     typesActivites?: array<int, string>,
 *     categories?: array<int, string>,
 *     familles?: array<int, string>,
 *     classes?: array<int, string>,
 *     classements?: array<int, string>,
 *     commissions: bool,
 *     groupements: bool,
 *     commune: bool
 * }
 */
final readonly class RessourcesSpecialiseesEtablissementTextGenerator
{
    public function __construct(private RessourcesSpecialiseesZoneTexter $zoneTexter, private RessourcesSpecialiseesHabitationTexter $habitationTexter, private RessourcesSpecialiseesIghTexter $ighTexter, private RessourcesSpecialiseesCelluleTexter $celluleTexter, private RessourcesSpecialiseesErpTexter $erpTexter, private RessourcesSpecialiseesBupTexter $bupTexter, private RessourcesSpecialiseesCampingTexter $campingTexter, private RessourcesSpecialiseesIopTexter $iopTexter, private RessourcesSpecialiseesManifestationTemporaireTexter $manifestationTemporaireTexter)
    {
    }

    public function fromRequest(Request $request): string
    {
        /** @var FormData $formData */
        $formData = $request->request->all('ressource_specialisee_etablissement');
        $formText = $formData['text'];

        if ('' !== $formText) {
            return $formText;
        }

        return $this->createTexter($formData['genre'])->generateText($formData);
    }

    private function createTexter(int $genreId): RessourcesSpecialiseesEtablissementTexter
    {
        return match ($genreId) {
            2 => $this->erpTexter,
            3 => $this->celluleTexter,
            4 => $this->habitationTexter,
            5 => $this->ighTexter,
            6 => $this->bupTexter,
            7 => $this->campingTexter,
            8 => $this->manifestationTemporaireTexter,
            9 => $this->iopTexter,
            10 => $this->zoneTexter,
            default => throw new \InvalidArgumentException(\sprintf('Genre %d inconnu', $genreId)),
        };
    }
}
