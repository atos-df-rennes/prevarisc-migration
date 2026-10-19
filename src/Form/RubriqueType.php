<?php

namespace App\Form;

use App\Entity\Rubrique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RubriqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('defaultDisplay', CheckboxType::class, [
                'label' => 'Afficher par défaut',
                'required' => false,
            ])
            ->add('ouverte', CheckboxType::class, [
                'label' => 'Ouvrir par défaut',
                'required' => false,
                'help' => "Cette option contrôle le mode d'ouverture par défaut du panneau de la rubrique."
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rubrique::class,
        ]);
    }
}
