<?php

namespace App\Form;

use App\Entity\PrescriptionArticleListe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrescriptionArticleListeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => "Libellé de l'article",
                'required' => false,
            ])

            ->add('visible', ChoiceType::class, [
                'label' => "Visibilité de l'article",
                'choices' => [
                    'Afficher' => true,
                    'Masquer' => false,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionArticleListe::class,
        ]);
    }
}
