<?php

namespace App\Form\Type\Descriptif;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ select (liste déroulante) pour les formulaires personnalisés.
 *
 * Les choices doivent être passées en option lors de l'instanciation.
 */
class ChampSelectType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => 'Sélectionnez un élément',
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
