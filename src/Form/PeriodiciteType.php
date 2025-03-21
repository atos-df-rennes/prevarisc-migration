<?php

namespace App\Form;

use App\Entity\Periodicite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeriodiciteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('periodicite', IntegerType::class, [
                'label' => 'Périodicité',
                'required' => false,
                'empty_data' => 0,
            ])
            ->add('localSommeil', CheckboxType::class)
            ->add('categorie', IntegerType::class, [
                'label' => 'Catégorie',
            ])
            ->add('type', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Periodicite::class,
        ]);
    }
}
