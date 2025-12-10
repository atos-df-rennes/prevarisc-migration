<?php

namespace App\Tests\Enum;

use App\Enum\PlatauStatutAvisEnum;
use PHPUnit\Framework\TestCase;

class PlatauStatutAvisEnumTest extends TestCase
{
    public function testGetLabelReturnsCorrectLabelForInconnu(): void
    {
        $result = PlatauStatutAvisEnum::getLabel(PlatauStatutAvisEnum::INCONNU);

        $this->assertSame('Statut inconnu', $result);
    }

    public function testGetLabelReturnsCorrectLabelForEnCours(): void
    {
        $result = PlatauStatutAvisEnum::getLabel(PlatauStatutAvisEnum::EN_COURS);

        $this->assertSame('En attente de complétude sur Prevarisc', $result);
    }

    public function testGetLabelReturnsCorrectLabelForTraite(): void
    {
        $result = PlatauStatutAvisEnum::getLabel(PlatauStatutAvisEnum::TRAITE);

        $this->assertSame('Traité sur Plat\'AU', $result);
    }

    public function testGetLabelReturnsCorrectLabelForARenvoyer(): void
    {
        $result = PlatauStatutAvisEnum::getLabel(PlatauStatutAvisEnum::A_RENVOYER);

        $this->assertSame('Traité sur Plat\'AU (en attente de renvoi)', $result);
    }

    public function testGetLabelReturnsCorrectLabelForEnErreur(): void
    {
        $result = PlatauStatutAvisEnum::getLabel(PlatauStatutAvisEnum::EN_ERREUR);

        $this->assertSame("En erreur sur Plat'AU", $result);
    }

    public function testGetLabelReturnsInconnuLabelWhenNullProvided(): void
    {
        $result = PlatauStatutAvisEnum::getLabel(null);

        $this->assertSame('Statut inconnu', $result);
    }

    public function testGetLabelReturnsNullForInvalidValue(): void
    {
        $result = PlatauStatutAvisEnum::getLabel('invalid_status');

        $this->assertSame('Statut inconnu', $result);
    }

    public function testConstantValues(): void
    {
        $this->assertSame('unknown', PlatauStatutAvisEnum::INCONNU);
        $this->assertSame('in_progress', PlatauStatutAvisEnum::EN_COURS);
        $this->assertSame('treated', PlatauStatutAvisEnum::TRAITE);
        $this->assertSame('to_export', PlatauStatutAvisEnum::A_RENVOYER);
        $this->assertSame('in_error', PlatauStatutAvisEnum::EN_ERREUR);
    }
}
