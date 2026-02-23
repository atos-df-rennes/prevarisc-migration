<?php

namespace App\Form;

use App\Entity\Commission;
use App\Entity\CommissionRegle;
use App\Entity\CommissionRegleEtudeVisite;
use App\Entity\CommissionRegleLocalSommeil;
use App\Entity\Commune;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementType;
use App\Entity\Groupement;
use App\Entity\GroupementType;
use App\Entity\IghClasse;
use App\Form\DataTransformer\CommissionRegleCollectionTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommissionRegleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Commission $commission */
        $commission = $options['commission'];
        $isCommunale = $commission->getType() && 'Communale' === $commission->getType()->getLibelle();

        // Zone géographique : Commune OU Groupement selon type de commission
        if ($isCommunale) {
            $builder->add('commune', EntityType::class, [
                'class' => Commune::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionnez une commune',
                'required' => false,
                'label' => 'Commune',
                'attr' => [
                    'class' => 'tom-select',
                    'data-placeholder' => 'Sélectionnez une commune',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.libelle', 'ASC');
                },
            ]);
        } else {
            $builder->add('groupement', EntityType::class, [
                'class' => Groupement::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionnez un groupement',
                'required' => false,
                'label' => 'Groupement',
                'attr' => [
                    'class' => 'tom-select',
                    'data-placeholder' => 'Sélectionnez un groupement',
                ],
                'group_by' => function (Groupement $groupement): ?string {
                    return $groupement->getType() instanceof GroupementType ? $groupement->getType()->getLibelle() : 'Autre';
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->leftJoin('g.type', 't')
                        ->addSelect('t')
                        ->orderBy('t.id', 'ASC')
                        ->addOrderBy('g.libelle', 'ASC');
                },
            ]);
        }

        // Catégories ERP - Checkbox avec bouton "Tout sélectionner"
        $builder->add('categories', EntityType::class, [
            'class' => EtablissementCategorie::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Catégories ERP',
            'attr' => [
                'class' => 'checkbox-group',
                'data-select-all-target' => 'categories',
            ],
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->orderBy('c.libelle', 'ASC');
            },
        ]);

        // Types ERP - Select multiple (TomSelect) avec bouton "Tout sélectionner"
        $builder->add('types', EntityType::class, [
            'class' => EtablissementType::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => false,
            'required' => false,
            'label' => 'Types ERP',
            'attr' => [
                'class' => 'tom-select',
                'multiple' => 'multiple',
                'data-select-all-target' => 'types',
                'data-placeholder' => 'Sélectionnez les types',
            ],
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')
                    ->orderBy('t.libelle', 'ASC');
            },
        ]);

        // Classes IGH - Select multiple (TomSelect) avec bouton "Tout sélectionner"
        $builder->add('classes', EntityType::class, [
            'class' => IghClasse::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => false,
            'required' => false,
            'label' => 'Classes IGH',
            'attr' => [
                'class' => 'tom-select',
                'multiple' => 'multiple',
                'data-select-all-target' => 'classes',
                'data-placeholder' => 'Sélectionnez les classes',
            ],
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->orderBy('c.libelle', 'ASC');
            },
        ]);

        // Local Sommeil - Checkboxes mappées via DataTransformer (Collection ↔ array<int>)
        $builder->add('localSommeil', ChoiceType::class, [
            'choices' => [
                'Oui' => 1,
                'Non' => 0,
            ],
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Local à sommeil',
            'attr' => [
                'class' => 'checkbox-group',
            ],
        ]);

        $builder->get('localSommeil')->addModelTransformer(
            new CommissionRegleCollectionTransformer(
                CommissionRegleLocalSommeil::class,
                'getLocalSommeil',
                'setLocalSommeil'
            )
        );

        // Étude/Visite - Checkboxes mappées via DataTransformer (Collection ↔ array<int>)
        $builder->add('etudeVisite', ChoiceType::class, [
            'choices' => [
                'Étude' => 1,
                'Visite' => 0,
            ],
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Type de dossier',
            'attr' => [
                'class' => 'checkbox-group',
            ],
        ]);

        $builder->get('etudeVisite')->addModelTransformer(
            new CommissionRegleCollectionTransformer(
                CommissionRegleEtudeVisite::class,
                'getEtudeVisite',
                'setEtudeVisite'
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommissionRegle::class,
        ]);

        $resolver->setRequired('commission');
        $resolver->setAllowedTypes('commission', Commission::class);
    }
}
