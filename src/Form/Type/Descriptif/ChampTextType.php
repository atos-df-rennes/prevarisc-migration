<?php

namespace App\Form\Type\Descriptif;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ texte simple pour les formulaires personnalisés.
 */
class ChampTextType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'form-control'],
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
