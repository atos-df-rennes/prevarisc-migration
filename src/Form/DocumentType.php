<?php

namespace App\Form;

use App\Dto\FormDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commission', ChoiceType::class, [
                'choices' => $builder->getData(),
                'choice_label' => fn (FormDocument $commission): string => $commission->nom(),
                'choice_value' => fn (?FormDocument $commission): int|string => $commission?->id() ?? '',
                'group_by' => fn (FormDocument $commission): ?string => $commission->type()
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier à envoyer',
                'constraints' => [
                    new File(mimeTypes: [
                        'application/vnd.oasis.opendocument.text',
                    ], mimeTypesMessage: "Le document n'est pas un document ODT.")
                ],
            ])
            ->add('close', ButtonType::class, [
                'label' => 'Fermer',
                'attr' => ['class' => 'btn-default', 'data-dismiss' => 'modal'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'btn-success', 'id' => 'document_submit'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
