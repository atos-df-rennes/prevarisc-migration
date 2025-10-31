<?php

namespace App\Service\RessourcesSpecialisees;

use App\Entity\DossierNatureListe;
use App\Interfaces\RessourcesSpecialiseesEtablissementTexter;
use App\Repository\DossierNatureListeRepository;
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

class RessourcesSpecialiseesEtablissementTextGenerator
{
    /** @var RessourcesSpecialiseesZoneTexter */
    private $zoneTexter;

    /** @var RessourcesSpecialiseesHabitationTexter */
    private $habitationTexter;

    /** @var \App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesIghTexter */
    private $ighTexter;

    /** @var \App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesCelluleTexter */
    private $celluleTexter;

    /** @var \App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesErpTexter */
    private $erpTexter;

    public function __construct(
        RessourcesSpecialiseesZoneTexter $zoneTexter,
        RessourcesSpecialiseesHabitationTexter $habitationTexter,
        RessourcesSpecialiseesIghTexter $ighTexter,
        RessourcesSpecialiseesCelluleTexter $celluleTexter,
        RessourcesSpecialiseesErpTexter $erpTexter
    )
    {
        $this->zoneTexter = $zoneTexter;
        $this->habitationTexter = $habitationTexter;
        $this->ighTexter = $ighTexter;
        $this->celluleTexter = $celluleTexter;
        $this->erpTexter = $erpTexter;
    }

    public function fromRequest(Request $request): string
    {
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
                $texter = new RessourcesSpecialiseesBupTexter();
                break;
            case 7:
                $texter = new RessourcesSpecialiseesCampingTexter();
                break;
            case 8:
                $texter = new RessourcesSpecialiseesManifestationTemporaireTexter();
                break;
            case 9:
                $texter = new RessourcesSpecialiseesIopTexter();
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
