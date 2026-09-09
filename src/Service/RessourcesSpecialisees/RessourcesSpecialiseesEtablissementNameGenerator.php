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
 * @phpstan-import-type FormData from RessourcesSpecialiseesEtablissementTextGenerator
 */
class RessourcesSpecialiseesEtablissementNameGenerator
{
    public static function fromRequest(Request $request): string
    {
        /** @var FormData $formData */
        $formData = $request->request->all();

        $name = 'etablissement_';
        $namer = self::createNamer($formData['genre']);

        return $name.$namer->generateName($formData);
    }

    private static function createNamer(int $genreId): RessourcesSpecialiseesEtablissementNamer
    {
        return match ($genreId) {
            2 => new RessourcesSpecialiseesErpNamer(),
            3 => new RessourcesSpecialiseesCelluleNamer(),
            4 => new RessourcesSpecialiseesHabitationNamer(),
            5 => new RessourcesSpecialiseesIghNamer(),
            6 => new RessourcesSpecialiseesBupNamer(),
            7 => new RessourcesSpecialiseesCampingNamer(),
            8 => new RessourcesSpecialiseesManifestationTemporaireNamer(),
            9 => new RessourcesSpecialiseesIopNamer(),
            10 => new RessourcesSpecialiseesZoneNamer(),
            default => throw new \InvalidArgumentException(\sprintf('Genre %d inconnu', $genreId)),
        };
    }
}
