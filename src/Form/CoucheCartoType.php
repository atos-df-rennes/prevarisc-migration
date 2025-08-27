<?php

namespace App\Form;

use App\Entity\CoucheCarto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoucheCartoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de la couche',
                'choices' => [
                    'WMS' => 'WMS',
                    'WMTS' => 'WMTS',
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de la couche',
            ])
            ->add('url', UrlType::class, [
                'label' => 'Url du flux',
            ])
            ->add('baseLayer', CheckboxType::class, [
                'label' => 'Oui',
                'required' => false,
            ])
            ->add('layer', TextType::class, [
                'label' => 'Nom de la couche dans le flux'
            ])
            ->add('format', ChoiceType::class, [
                'label' => "Format de l'image de la couche",
                'choices' => [
                    'image/png' => 'image/png',
                    'image/jpeg' => 'image/jpeg',
                ]
            ])
            ->add('transparent', CheckboxType::class, [
                'label' => 'Oui',
                'required' => false,
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
