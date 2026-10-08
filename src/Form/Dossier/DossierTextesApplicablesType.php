<?php

namespace App\Form\Dossier;

use App\Entity\Dossier;
use App\Entity\TexteApplicable;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierTextesApplicablesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('textesApplicables', EntityType::class, [
                'label' => false,
                'class' => TexteApplicable::class,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
                    ->where('t.visible = 1')
                    ->orderBy('t.type', 'ASC')
                    ->addOrderBy('t.num', 'ASC'),
                'choice_label' => fn (TexteApplicable $texteApplicable): ?string => $texteApplicable->getLibelle(),
                'choice_attr' => function (TexteApplicable $texteApplicable): array {
                    $type = $texteApplicable->getType();

                    return ['data-type' => ($type?->getLibelle())];
                },
                'multiple' => true,
                'expanded' => true,
                'group_by' => function (TexteApplicable $texteApplicable): ?int {
                    $type = $texteApplicable->getType();

                    return $type?->getId();
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);
    }
}
