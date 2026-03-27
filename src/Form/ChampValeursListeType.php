<?php

namespace App\Form;

use App\Entity\ChampValeurListe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampValeursListeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('valeur', TextType::class, [
                'label' => false,
                'row_attr' => ['class' => 'col-xs-10'],
                'attr' => ['list' => 'champ-valeurs-liste', 'autocomplete' => 'off']
            ])
            ->add('idx', HiddenType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChampValeurListe::class,
        ]);
    }
}
