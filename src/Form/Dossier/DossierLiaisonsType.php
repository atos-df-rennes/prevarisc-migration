<?php

namespace App\Form\Dossier;

use App\Entity\Dossier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class DossierLiaisonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('dossiersToAdd', EntityType::class, [
            'label' => false,
            'class' => Dossier::class,
            'choices' => $options['available_dossiers'],
            'choice_label' => 'id',
            'multiple' => true,
            'expanded' => false,
            'mapped' => false,
            'required' => true,
            'error_bubbling' => true,
            'constraints' => [
                new Count(['min' => 1, 'minMessage' => 'Veuillez sélectionner au moins un dossier à lier.']),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('available_dossiers')
            ->setAllowedTypes('available_dossiers', 'iterable');
    }
}
