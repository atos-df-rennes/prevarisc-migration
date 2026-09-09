<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType;
use App\Validator\DossierObjet;
use App\Validator\DossierObjetValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Tests unitaires pour le validator DossierObjet.
 *
 * Ce validator vérifie que le champ OBJET est renseigné
 * sauf pour les natures 21, 26 (visites périodiques) et 40, 41, 42, 44 (arrêtés).
 */
final class DossierObjetValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DossierObjetValidator
    {
        return new DossierObjetValidator();
    }

    /**
     * Test avec une nature requérant OBJET (nature 1) et objet renseigné.
     * Aucune violation ne doit être levée.
     */
    public function testValidateAvecObjetRequis(): void
    {
        $dossier = $this->createDossier(1, 'Mon objet de dossier');

        $this->validator->validate($dossier, new DossierObjet());

        $this->assertNoViolation();
    }

    /**
     * Test avec une nature requérant OBJET mais objet vide ou null.
     * Une violation doit être levée.
     */
    #[DataProvider('provideObjetVideOuNull')]
    public function testValidateAvecObjetRequisMaisVide(?string $objet): void
    {
        $dossier = $this->createDossier(1, $objet);
        $constraint = new DossierObjet();

        $this->validator->validate($dossier, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.objet')
            ->assertRaised()
        ;
    }

    /**
     * Fournit des cas de champ objet vide (null, chaîne vide, espaces).
     *
     * @return iterable<string, array{0: string|null}>
     */
    public static function provideObjetVideOuNull(): iterable
    {
        yield 'objet null' => [null];
        yield 'objet vide' => [''];
        yield 'objet avec espaces' => ['   '];
    }

    /**
     * Test avec des natures NE requérant PAS OBJET (21, 26).
     * Aucune violation ne doit être levée même si objet est vide.
     */
    #[DataProvider('provideNaturesSansObjet')]
    public function testValidateAvecNatureSansObjet(int $natureId): void
    {
        $dossier = $this->createDossier($natureId, '');

        $this->validator->validate($dossier, new DossierObjet());

        $this->assertNoViolation();
    }

    /**
     * Fournit les IDs des natures ne requérant pas le champ OBJET.
     *
     * @return iterable<string, array{0: int}>
     */
    public static function provideNaturesSansObjet(): iterable
    {
        yield 'nature 21 - Visite périodique' => [21];
        yield 'nature 26 - Groupe visite périodique' => [26];
        yield 'nature 40 - Arrêté ouverture' => [40];
        yield 'nature 41 - Arrêté' => [41];
        yield 'nature 42 - Arrêté' => [42];
        yield 'nature 44 - Arrêté' => [44];
    }

    /**
     * Test avec un dossier sans nature.
     * Aucune violation ne doit être levée (sera géré par @Assert\Count sur nature).
     */
    public function testValidateSansNature(): void
    {
        $type = new DossierType();
        $type->setLibelle('Étude');

        $dossier = new Dossier();
        $dossier->setType($type);
        $dossier->setObjet('');

        $this->validator->validate($dossier, new DossierObjet());

        $this->assertNoViolation();
    }

    /**
     * Crée un dossier de test avec une nature donnée.
     *
     * @param int         $natureId ID de la nature
     * @param string|null $objet    Objet du dossier
     */
    private function createDossier(int $natureId, ?string $objet): Dossier
    {
        $type = new DossierType();
        $type->setLibelle('Étude');

        $nature = $this->createMock(DossierNatureListe::class);
        $nature
            ->method('getId')
            ->willReturn($natureId)
        ;

        $dossier = new Dossier();
        $dossier->setType($type);
        $dossier->setObjet($objet);
        $dossier->setNature($nature);

        return $dossier;
    }
}
