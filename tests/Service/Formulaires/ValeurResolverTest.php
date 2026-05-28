<?php

namespace App\Tests\Service\Formulaires;

use App\Constant\TypeChamp;
use App\Entity\TypeChamp as TypeChampEntity;
use App\Entity\Valeur;
use App\Service\Formulaires\ValeurResolver;
use App\Utils\DateFormatterFr;
use PHPUnit\Framework\TestCase;

final class ValeurResolverTest extends TestCase
{
    public function testRetourneVideSiValeurNulle(): void
    {
        self::assertSame('', ValeurResolver::resolve(null, null));
    }

    public function testRetourneVideSiValeurNulleAvecTypeChamp(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);

        self::assertSame('', ValeurResolver::resolve($typeChamp, null));
    }

    public function testRetourneNonSiValeurNulleAvecTypeChampCheckbox(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::CHECKBOX);

        self::assertSame('Non', ValeurResolver::resolve($typeChamp, null));
    }

    public function testCheckboxVraiRetourneOui(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::CHECKBOX);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getCheckbox')->willReturn(true);

        self::assertSame('Oui', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testCheckboxFauxRetourneNon(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::CHECKBOX);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getCheckbox')->willReturn(false);

        self::assertSame('Non', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testDateNulleRetourneVide(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::DATE);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getDate')->willReturn(null);

        self::assertSame('', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testDateNonNulleRetourneDateFormatee(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::DATE);
        $date = new \DateTime('2024-06-15');
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getDate')->willReturn($date);

        self::assertSame(DateFormatterFr::formatLong($date), ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testTextareaRetourneLongStr(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::TEXTAREA);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getLongStr')->willReturn('Texte long ici');

        self::assertSame('Texte long ici', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testNumberRetourneValeurNumerique(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::NUMBER);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getNumber')->willReturn(42);

        self::assertSame('42', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testNumberNulRetourneVide(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::NUMBER);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getNumber')->willReturn(null);

        self::assertSame('', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testDefautRetourneStr(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::TEXT);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getStr')->willReturn('valeur texte');

        self::assertSame('valeur texte', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testDefautAvecStrNullFallbackSurLongStr(): void
    {
        $typeChamp = $this->createMock(TypeChampEntity::class);
        $typeChamp->method('getId')->willReturn(TypeChamp::TEXT);
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getStr')->willReturn(null);
        $valeur->method('getLongStr')->willReturn('fallback long');

        self::assertSame('fallback long', ValeurResolver::resolve($typeChamp, $valeur));
    }

    public function testTypeChampNullFallbackSurStr(): void
    {
        $valeur = $this->createMock(Valeur::class);
        $valeur->method('getStr')->willReturn('valeur sans type');

        self::assertSame('valeur sans type', ValeurResolver::resolve(null, $valeur));
    }
}
