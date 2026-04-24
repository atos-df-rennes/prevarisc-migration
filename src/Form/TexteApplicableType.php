<?php

namespace App\Form;

use App\Entity\TexteApplicable;
use App\Entity\TypeTexteApplicable;
use App\Form\DataTransformer\BooleanStringTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TexteApplicableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextareaType::class)
            ->add('visible', ChoiceType::class, [
                // Valeurs en string '1'/'0' pour cohérence avec BooleanStringTransformer.
                'choices' => [
                    'Afficher' => '1',
                    'Masquer' => '0',
                ],
            ])
            ->add('num', HiddenType::class, [
                'empty_data' => 9999,
            ])
            ->add('type', EntityType::class, [
                'class' => TypeTexteApplicable::class,
                'choice_label' => 'libelle',
            ])
            ->add('prescription', ChoiceType::class, [
                'label' => 'Rendre ce texte disponible pour les prescriptions',
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                // Valeurs en string '1'/'0' pour que le BooleanStringTransformer
                // puisse faire le mapping bool ↔ string de façon cohérente.
                'choices' => [
                    'Oui' => '1',
                    'Non' => '0',
                ],
                'data' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sauvegarder',
                'attr' => ['class' => 'btn btn-success'],
            ])
        ;

        // Model transformer : bool (modèle) ↔ string '1'/'0' (normalisé).
        // Cela permet à getData() de retourner directement un bool dans le controller,
        // sans avoir à appeler filter_var() manuellement.
        $builder->get('prescription')->addModelTransformer(new BooleanStringTransformer());
        $builder->get('visible')->addModelTransformer(new BooleanStringTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TexteApplicable::class,
        ]);
    }
}
