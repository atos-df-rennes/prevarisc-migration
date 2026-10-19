<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Extension pour ajouter des fonctions personnalisées Twig permettant d'accéder aux paramètres du parameterBag.
 */
final class ParameterExtension
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    /**
     * Récupère la valeur d'un paramètre depuis le parameterBag.
     *
     * @param string $parameterName nom du paramètre à récupérer
     *
     * @return array|bool|string|int|float|null valeur du paramètre
     */
    #[AsTwigFunction(name: 'parameter')] // @phpstan-ignore-line
    public function parameter(string $parameterName): bool|string|int|float|\UnitEnum|array|null
    {
        return $this->parameterBag->get($parameterName);
    }
}
