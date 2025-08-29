<?php

namespace App\Form;

use App\Entity\Etablissement;
use App\Entity\TexteApplicable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextesApplicablesEtablissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('textesApplicables', EntityType::class, [
                'label' => false,
                'class' => TexteApplicable::class,
                'choice_label' => function (TexteApplicable $texteApplicable): ?string {
                    return $texteApplicable->getLibelle();
                },
                'choice_attr' => function (TexteApplicable $texteApplicable): array {
                    $type = $texteApplicable->getType();

                    return ['data-type' => ($type != null ? $type->getLibelle():null)];
                },
                'multiple' => true,
                'expanded' => true,
                'group_by' => function (TexteApplicable $texteApplicable): ?int {
                    $type = $texteApplicable->getType();

                    return ($type != null ? $type->getId():null);
                }
            ])
            ->add('sauvegarder', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->add('annuler', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etablissement::class,
        ]);
    }
}
