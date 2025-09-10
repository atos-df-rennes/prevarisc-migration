<?php

namespace App\Form;

use App\Entity\Etablissement;
use App\Entity\TexteApplicable;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.visible = 1')
                    ;
                },
                'choice_label' => function (TexteApplicable $texteApplicable): ?string {
                    return $texteApplicable->getLibelle();
                },
                'choice_attr' => function (TexteApplicable $texteApplicable): array {
                    $type = $texteApplicable->getType();

                    return ['data-type' => ($type !== null ? $type->getLibelle() : null)];
                },
                'multiple' => true,
                'expanded' => true,
                'group_by' => function (TexteApplicable $texteApplicable): ?int {
                    $type = $texteApplicable->getType();

                    return ($type !== null ? $type->getId() : null);
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etablissement::class,
        ]);
    }
}
