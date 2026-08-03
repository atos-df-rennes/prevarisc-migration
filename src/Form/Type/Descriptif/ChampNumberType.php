<?php

namespace App\Form\Type\Descriptif;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ numérique entier pour les formulaires personnalisés.
 */
class ChampNumberType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['min' => 0],
            'required' => false,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return IntegerType::class;
    }
}
