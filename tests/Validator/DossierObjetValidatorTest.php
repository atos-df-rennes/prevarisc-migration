<?php

namespace App\Tests\Validator;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType;
use App\Validator\DossierObjet;
use App\Validator\DossierObjetValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests unitaires pour le validator DossierObjet.
 *
 * Ce validator vérifie que le champ OBJET est renseigné
 * sauf pour les natures 21 et 26 (visites périodiques).
 */
class DossierObjetValidatorTest extends TestCase
{
    /** @var DossierObjetValidator */
    private $validator;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    protected function setUp(): void
    {
        $this->validator = new DossierObjetValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * Test avec une nature requérant OBJET (nature 1) et objet renseigné.
     * Aucune violation ne doit être levée.
     */
    public function testValidateAvecObjetRequis(): void
    {
        $dossier = $this->createDossier(1, 'Mon objet de dossier');

        $this->context
            ->expects(self::never())
            ->method('buildViolation')
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
    }

    /**
     * Test avec une nature requérant OBJET (nature 1) mais objet vide.
     * Une violation doit être levée.
     */
    public function testValidateAvecObjetRequisMaisVide(): void
    {
        $dossier = $this->createDossier(1, '');

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->expects(self::once())
            ->method('atPath')
            ->with('objet')
            ->willReturnSelf()
        ;
        $violationBuilder
            ->expects(self::once())
            ->method('addViolation')
        ;

        $this->context
            ->expects(self::once())
            ->method('buildViolation')
            ->willReturn($violationBuilder)
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
    }

    /**
     * Test avec une nature requérant OBJET (nature 1) mais objet null.
     * Une violation doit être levée.
     */
    public function testValidateAvecObjetRequisMaisNull(): void
    {
        $dossier = $this->createDossier(1, null);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->expects(self::once())
            ->method('atPath')
            ->with('objet')
            ->willReturnSelf()
        ;
        $violationBuilder
            ->expects(self::once())
            ->method('addViolation')
        ;

        $this->context
            ->expects(self::once())
            ->method('buildViolation')
            ->willReturn($violationBuilder)
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
    }

    /**
     * Test avec une nature NE requérant PAS OBJET (nature 21 - visite périodique).
     * Aucune violation ne doit être levée même si objet est vide.
     */
    public function testValidateAvecNature21SansObjet(): void
    {
        $dossier = $this->createDossier(21, '');

        $this->context
            ->expects(self::never())
            ->method('buildViolation')
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
    }

    /**
     * Test avec une nature NE requérant PAS OBJET (nature 26 - groupe visite périodique).
     * Aucune violation ne doit être levée même si objet est null.
     */
    public function testValidateAvecNature26SansObjet(): void
    {
        $dossier = $this->createDossier(26, null);

        $this->context
            ->expects(self::never())
            ->method('buildViolation')
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
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

        $this->context
            ->expects(self::never())
            ->method('buildViolation')
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
    }

    /**
     * Test avec objet contenant uniquement des espaces.
     * Une violation doit être levée pour une nature requérant OBJET.
     */
    public function testValidateAvecObjetUniquementEspaces(): void
    {
        $dossier = $this->createDossier(1, '   ');

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->expects(self::once())
            ->method('atPath')
            ->with('objet')
            ->willReturnSelf()
        ;
        $violationBuilder
            ->expects(self::once())
            ->method('addViolation')
        ;

        $this->context
            ->expects(self::once())
            ->method('buildViolation')
            ->willReturn($violationBuilder)
        ;

        $constraint = new DossierObjet();
        $this->validator->validate($dossier, $constraint);
    }

    /**
     * Crée un dossier de test avec une nature donnée.
     *
     * @param int         $natureId ID de la nature
     * @param string|null $objet    Objet du dossier
     *
     * @return Dossier
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
