<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Formulaire d'upload d'un courrier type pour un membre de commission.
 * Accepte uniquement les fichiers ODT.
 */
class CommissionMembreCourrierUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('courrier', FileType::class, [
            'label' => false,
            'required' => true,
            'constraints' => [
                new File([
                    'mimeTypes' => [
                        'application/vnd.oasis.opendocument.text',
                    ],
                    'mimeTypesMessage' => 'Seuls les fichiers ODT (.odt) sont acceptés.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
