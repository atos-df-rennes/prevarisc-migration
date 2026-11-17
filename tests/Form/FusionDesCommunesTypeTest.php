<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\FusionDesCommunesType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Teste les validations du formulaire FusionDesCommunesType :
 *  - Validation de la présence d'un fichier
 *  - Validation du format JSON
 */
final class FusionDesCommunesTypeTest extends TestCase
{
    public function testFormTypeBuildsWithFichierField(): void
    {
        $type = new FusionDesCommunesType();

        $addedFields = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(
            function (string $name, ?string $fieldType = null, array $options = []) use (&$addedFields, $builder): FormBuilderInterface {
                $addedFields[$name] = ['type' => $fieldType, 'options' => $options];

                return $builder;
            }
        );

        $type->buildForm($builder, []);

        $this->assertArrayHasKey('fichier', $addedFields, 'Form should add a fichier field');
        $this->assertTrue($addedFields['fichier']['options']['required'] ?? false, 'fichier field should be required');
    }

    public function testFichierFieldHasValidationConstraints(): void
    {
        $type = new FusionDesCommunesType();

        $addedFields = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(
            function (string $name, ?string $fieldType = null, array $options = []) use (&$addedFields, $builder): FormBuilderInterface {
                $addedFields[$name] = ['type' => $fieldType, 'options' => $options];

                return $builder;
            }
        );

        $type->buildForm($builder, []);

        $constraints = $addedFields['fichier']['options']['constraints'] ?? [];
        $this->assertNotEmpty($constraints, 'fichier field should have validation constraints');

        $constraintTypes = array_map(get_class(...), $constraints);
        $this->assertContains(NotBlank::class, $constraintTypes, 'Should have NotBlank constraint');
        $this->assertContains(File::class, $constraintTypes, 'Should have File constraint');
    }

    public function testFileConstraintAllowsJsonMimeType(): void
    {
        $type = new FusionDesCommunesType();

        $addedFields = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(
            function (string $name, ?string $fieldType = null, array $options = []) use (&$addedFields, $builder): FormBuilderInterface {
                $addedFields[$name] = ['type' => $fieldType, 'options' => $options];

                return $builder;
            }
        );

        $type->buildForm($builder, []);

        $constraints = $addedFields['fichier']['options']['constraints'] ?? [];

        $fileConstraint = null;
        foreach ($constraints as $constraint) {
            if ($constraint instanceof File) {
                $fileConstraint = $constraint;
                break;
            }
        }

        $this->assertInstanceOf(File::class, $fileConstraint, 'File constraint should be present');
        $this->assertContains('application/json', $fileConstraint->mimeTypes, 'Should accept application/json mime type');
    }
}
