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
class RessourcesSpecialiseesEtablissementTextGenerator
{
    /** @var RessourcesSpecialiseesZoneTexter */
    private $zoneTexter;

    /** @var RessourcesSpecialiseesHabitationTexter */
    private $habitationTexter;

    /** @var RessourcesSpecialiseesIghTexter */
    private $ighTexter;

    /** @var RessourcesSpecialiseesCelluleTexter */
    private $celluleTexter;

    /** @var RessourcesSpecialiseesErpTexter */
    private $erpTexter;

    /** @var RessourcesSpecialiseesBupTexter */
    private $bupTexter;

    /** @var RessourcesSpecialiseesCampingTexter */
    private $campingTexter;

    /** @var RessourcesSpecialiseesIopTexter */
    private $iopTexter;

    /** @var RessourcesSpecialiseesManifestationTemporaireTexter */
    private $manifestationTemporaireTexter;

    public function __construct(
        RessourcesSpecialiseesZoneTexter $zoneTexter,
        RessourcesSpecialiseesHabitationTexter $habitationTexter,
        RessourcesSpecialiseesIghTexter $ighTexter,
        RessourcesSpecialiseesCelluleTexter $celluleTexter,
        RessourcesSpecialiseesErpTexter $erpTexter,
        RessourcesSpecialiseesBupTexter $bupTexter,
        RessourcesSpecialiseesCampingTexter $campingTexter,
        RessourcesSpecialiseesIopTexter $iopTexter,
        RessourcesSpecialiseesManifestationTemporaireTexter $manifestationTemporaireTexter
    ) {
        $this->zoneTexter = $zoneTexter;
        $this->habitationTexter = $habitationTexter;
        $this->ighTexter = $ighTexter;
        $this->celluleTexter = $celluleTexter;
        $this->erpTexter = $erpTexter;
        $this->bupTexter = $bupTexter;
        $this->campingTexter = $campingTexter;
        $this->iopTexter = $iopTexter;
        $this->manifestationTemporaireTexter = $manifestationTemporaireTexter;
    }

    public function fromRequest(Request $request): string
    {
        /** @var FormData $formData */
        $formData = $request->request->get('ressource_specialisee_etablissement');
        $formText = $formData['text'];

        if ('' !== $formText) {
            return $formText;
        }

        return $this->createTexter($formData['genre'])->generateText($formData);
    }

    private function createTexter(int $genreId): RessourcesSpecialiseesEtablissementTexter
    {
        switch ($genreId) {
            case 2:
                $texter = $this->erpTexter;
                break;
            case 3:
                $texter = $this->celluleTexter;
                break;
            case 4:
                $texter = $this->habitationTexter;
                break;
            case 5:
                $texter = $this->ighTexter;
                break;
            case 6:
                $texter = $this->bupTexter;
                break;
            case 7:
                $texter = $this->campingTexter;
                break;
            case 8:
                $texter = $this->manifestationTemporaireTexter;
                break;
            case 9:
                $texter = $this->iopTexter;
                break;
            case 10:
                $texter = $this->zoneTexter;
                break;
            default:
                throw new \InvalidArgumentException(\sprintf('Genre %d inconnu', $genreId));
        }

        return $texter;
    }
}
