<?php

namespace App\Tests\Enum;

use App\Enum\PlatauStatutPecEnum;
use PHPUnit\Framework\TestCase;

class PlatauStatutPecEnumTest extends TestCase
{
    public function testGetLabelReturnsCorrectLabelForInconnu(): void
    {
        $result = PlatauStatutPecEnum::getLabel(PlatauStatutPecEnum::INCONNU);

        $this->assertSame('Statut inconnu', $result);
    }

    public function testGetLabelReturnsCorrectLabelForEnAttente(): void
    {
        $result = PlatauStatutPecEnum::getLabel(PlatauStatutPecEnum::EN_ATTENTE);

        $this->assertSame('En attente de complétude sur Prevarisc', $result);
    }

    public function testGetLabelReturnsCorrectLabelForPriseEnCompte(): void
    {
        $result = PlatauStatutPecEnum::getLabel(PlatauStatutPecEnum::PRISE_EN_COMPTE);

        $this->assertSame("Prise en compte sur Plat'AU", $result);
    }

    public function testGetLabelReturnsCorrectLabelForARenvoyer(): void
    {
        $result = PlatauStatutPecEnum::getLabel(PlatauStatutPecEnum::A_RENVOYER);

        $this->assertSame("Prise en compte sur Plat'AU (en attente de renvoi)", $result);
    }

    public function testGetLabelReturnsCorrectLabelForEnErreur(): void
    {
        $result = PlatauStatutPecEnum::getLabel(PlatauStatutPecEnum::EN_ERREUR);

        $this->assertSame("En erreur sur Plat'AU", $result);
    }

    public function testGetLabelReturnsInconnuLabelWhenNullProvided(): void
    {
        $result = PlatauStatutPecEnum::getLabel(null);

        $this->assertSame('Statut inconnu', $result);
    }

    public function testGetLabelReturnsNullForInvalidValue(): void
    {
        $result = PlatauStatutPecEnum::getLabel('invalid_status');

        $this->assertSame('Statut inconnu', $result);
    }

    public function testConstantValues(): void
    {
        $this->assertSame('unknown', PlatauStatutPecEnum::INCONNU);
        $this->assertSame('awaiting', PlatauStatutPecEnum::EN_ATTENTE);
        $this->assertSame('taken_into_account', PlatauStatutPecEnum::PRISE_EN_COMPTE);
        $this->assertSame('to_export', PlatauStatutPecEnum::A_RENVOYER);
        $this->assertSame('in_error', PlatauStatutPecEnum::EN_ERREUR);
    }
}
