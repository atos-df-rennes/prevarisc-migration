<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // Conserver la configuration Doctrine principale en environnement de test.
    // Le surchargement historique du dbname déclenche une dépréciation sous DoctrineBundle 2.13
    // et la base dédiée n'est pas disponible dans l'environnement de validation courant.
};
