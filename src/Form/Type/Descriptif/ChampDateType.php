<?php

namespace App\Form\Type\Descriptif;

use App\Form\DataTransformer\DateToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champ date pour les formulaires personnalisés.
 *
 * Rendu comme un input texte au format d/m/Y avec le datepicker Bootstrap.
 * Un DataTransformer assure la conversion \DateTime ↔ string.
 */
class ChampDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DateToStringTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'date'],
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
