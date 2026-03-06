<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\CommissionDocumentCrType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

/**
 * Teste le formulaire CommissionDocumentCrType.
 *
 * Note : la validation MIME type (contrainte File) repose sur finfo_file() avec
 * une vraie requête HTTP. Ces tests valident la structure du formulaire.
 * Les tests de rejet MIME type relèvent de tests fonctionnels (@group functional).
 */
final class CommissionDocumentCrTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator()),
        ];
    }

    public function testFormulaireContientChampDocumentCrDeTypeFile(): void
    {
        $form = $this->factory->create(CommissionDocumentCrType::class);

        self::assertTrue($form->has('documentCr'));
        self::assertInstanceOf(FileType::class, $form->get('documentCr')->getConfig()->getType()->getInnerType());
    }

    public function testChampDocumentCrPossedeContrainteMimeTypeOdt(): void
    {
        $form = $this->factory->create(CommissionDocumentCrType::class);
        $constraints = $form->get('documentCr')->getConfig()->getOption('constraints');

        self::assertCount(1, $constraints);
        self::assertInstanceOf(File::class, $constraints[0]);

        /** @var File $fileConstraint */
        $fileConstraint = $constraints[0];
        self::assertContains('application/vnd.oasis.opendocument.text', $fileConstraint->mimeTypes);
    }
}
