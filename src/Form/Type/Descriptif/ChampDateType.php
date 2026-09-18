<?php

namespace App\Form\Type\Descriptif;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ date pour les formulaires personnalisés.
 *
 * Rendu comme un input texte au format d/m/Y avec le datepicker Bootstrap.
 * Un DataTransformer assure la conversion \DateTime ↔ string.
 */
class ChampDateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'required' => false,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return DateType::class;
    }
}
