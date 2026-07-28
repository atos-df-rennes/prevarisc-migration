<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension pour ajouter des fonctions personnalisées Twig permettant d'accéder aux paramètres du parameterBag.
 */
class ParameterExtension extends AbstractExtension
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('parameter', $this->parameter(...)),
        ];
    }

    /**
     * Récupère la valeur d'un paramètre depuis le parameterBag.
     *
     * @param string $parameterName nom du paramètre à récupérer
     *
     * @return array|bool|string|int|float|null valeur du paramètre
     */
    public function parameter(string $parameterName) // @phpstan-ignore-line
    {
        return $this->parameterBag->get($parameterName);
    }
}
