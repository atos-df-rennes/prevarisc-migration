<?php

namespace App\Service\RessourcesSpecialisees;

use App\Interfaces\RessourcesSpecialiseesEtablissementNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesBupNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesCampingNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesCelluleNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesErpNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesHabitationNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesIghNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesIopNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesManifestationTemporaireNamer;
use App\Service\RessourcesSpecialisees\Etablissement\Namer\RessourcesSpecialiseesZoneNamer;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type FormData from \App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesEtablissementNameGenerator
{
    public static function fromRequest(Request $request): string
    {
        /** @var FormData $formData */
        $formData = $request->request->get('ressource_specialisee_etablissement');

        $name = 'etablissement_';
        $namer = self::createNamer($formData['genre']);

        return $name.$namer->generateName($formData);
    }

    private static function createNamer(int $genreId): RessourcesSpecialiseesEtablissementNamer
    {
        switch ($genreId) {
            case 2:
                $namer = new RessourcesSpecialiseesErpNamer();
                break;
            case 3:
                $namer = new RessourcesSpecialiseesCelluleNamer();
                break;
            case 4:
                $namer = new RessourcesSpecialiseesHabitationNamer();
                break;
            case 5:
                $namer = new RessourcesSpecialiseesIghNamer();
                break;
            case 6:
                $namer = new RessourcesSpecialiseesBupNamer();
                break;
            case 7:
                $namer = new RessourcesSpecialiseesCampingNamer();
                break;
            case 8:
                $namer = new RessourcesSpecialiseesManifestationTemporaireNamer();
                break;
            case 9:
                $namer = new RessourcesSpecialiseesIopNamer();
                break;
            case 10:
                $namer = new RessourcesSpecialiseesZoneNamer();
                break;
            default:
                throw new \InvalidArgumentException(\sprintf('Genre %d inconnu', $genreId));
        }

        return $namer;
    }
}
