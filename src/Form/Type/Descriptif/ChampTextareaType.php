<?php

namespace App\Form\Type\Descriptif;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ textarea pour les formulaires personnalisés.
 */
final class ChampTextareaType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return TextareaType::class;
    }
}
