<?php

namespace App\Form;

use App\Entity\PrescriptionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PrescriptionTypePrescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', null, [
                'constraints' => [
                    new NotBlank(message: 'Le contenu de la prescription type ne peut pas être vide.'),
                ],
            ])
            ->add('associations', CollectionType::class, [
                'entry_type' => PrescriptionTypePrescriptionAssociationType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Une prescription type doit comporter au moins une association texte/article.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionType::class,
        ]);
    }
}
