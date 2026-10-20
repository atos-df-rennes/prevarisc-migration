<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Entity\Avis;
use App\Tests\Factory\AvisFactory;
use Zenstruck\Foundry\Story;

final class AvisStory extends Story
{
    public function build(): void
    {
        $this->addState('favorable', AvisFactory::createOne(['libelle' => 'Favorable']));
        $this->addState('defavorable', AvisFactory::createOne(['libelle' => 'Défavorable']));
    }

    public static function favorable(): Avis
    {
        return self::get('favorable');
    }

    public static function defavorable(): Avis
    {
        return self::get('defavorable');
    }
}
