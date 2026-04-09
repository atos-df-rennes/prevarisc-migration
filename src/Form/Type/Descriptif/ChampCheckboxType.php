<?php

namespace App\Form\Type\Descriptif;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ checkbox pour les formulaires personnalisés.
 */
class ChampCheckboxType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'false_values' => ['0', '', null],
        ]);
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}
