<?php

namespace App\Service\Formulaires\Fusion;

/**
 * Helpers spécifiques au nommage du champ de fusion.
 */
final class ChampNamer
{
    /**
     * Sépare toutes les options.
     *
     * @param string[] $options
     */
    public static function getFullFusionName(string $capsuleRubrique, array $options): string
    {
        $params = [];
        $params[] = $capsuleRubrique;

        $options = array_map(function (string $option): string {
            return ChampNamer::formatFusionName($option);
        }, $options);

        $params = array_merge($params, $options);

        return implode('-', $params);
    }

    /**
     * Définit le nom de la boucle. Chaque option est en minscules avec la 1ère lettre en majuscule.
     *
     * @param string $options Le format attendu est une série d'options séparées par un espace : "<option1> <option2> ...".
     */
    public static function getLoopName(string $options): string
    {
        $loopName = '';
        $strings = preg_split("/[\\s'\"\\/]+/", $options);

        if (false === $strings) {
            throw new \InvalidArgumentException('Invalid options format.');
        }

        foreach ($strings as $string) {
            $loopName .= ucfirst(strtolower($string));
        }

        return $loopName;
    }

    /**
     * Formate le nom du champ de fusion.
     * Les espaces sont supprimés et le nom est mis en minuscules.
     * Les caractères spéciaux sont remplacés par des underscores.
     */
    private static function formatFusionName(string $name): string
    {
        $trimmedName = trim($name);
        $loweredName = strtolower($trimmedName);

        $result = preg_replace(['/\s+/', "/'+/", '/\"+/'], '_', $loweredName);

        if (null === $result) {
            throw new \InvalidArgumentException('Invalid name format.');
        }

        return $result;
    }
}
