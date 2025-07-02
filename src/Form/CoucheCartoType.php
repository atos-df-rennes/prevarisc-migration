<?php

namespace App\Form;

use App\Entity\CoucheCarto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoucheCartoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('url')
            ->add('isBaseLayer')
            ->add('layers')
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'image/png' => 'image/png',
                    'image/jpeg' => 'image/jpeg',
                ]
            ])
            ->add('transparent')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'WMS' => 'WMS',
                    'WMTS' => 'WMTS',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CoucheCarto::class,
        ]);
    }
}
