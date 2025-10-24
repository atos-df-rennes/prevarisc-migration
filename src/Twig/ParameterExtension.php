<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension pour ajouter des fonctions personnalisées Twig permettant d'accéder aux paramètres du parameterBag.
 */
class ParameterExtension extends AbstractExtension
{
    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag) {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('parameter', [$this, 'parameter']),
        ];
    }

    /**
     * Récupère la valeur d'un paramètre depuis le parameterBag.
     *
     * @param string $parameterName Nom du paramètre à récupérer.
     * @return array|bool|string|int|float|\UnitEnum|null Valeur du paramètre.
     */
    public function parameter(string $parameterName)
    {
        return $this->parameterBag->get($parameterName);
    }
}
