<?php

namespace App\Service\RessourcesSpecialisees;

use App\Interfaces\RessourcesSpecialiseesEtablissement;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesBupNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesCampingNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesCelluleNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesErpNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesHabitationNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesIghNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesIopNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesManifestationTemporaireNamer;
use App\Service\RessourcesSpecialisees\Etablissement\RessourcesSpecialiseesZoneNamer;
use Symfony\Component\HttpFoundation\Request;

class RessourcesSpecialiseesEtablissementNameGenerator
{
    public static function fromRequest(Request $request): string
    {
        $formData = $request->request->get('ressource_specialisee_etablissement');
        $text = $formData['text'];

        if ('' !== $text) {
            return $text;
        }

        $name = 'etablissement_';
        $namer = self::createNamer($formData['genre']);

        return $name.$namer->generateName($formData);
    }

    private static function createNamer(int $genreId): RessourcesSpecialiseesEtablissement
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
