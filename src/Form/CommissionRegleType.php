<?php

namespace App\Form;

use App\Entity\Commission;
use App\Entity\CommissionRegle;
use App\Entity\Commune;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementType;
use App\Entity\Groupement;
use App\Entity\GroupementType;
use App\Entity\IghClasse;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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

        // Local Sommeil - Checkbox individuelles (Oui/Non/Les deux)
        $builder->add('localSommeilValues', ChoiceType::class, [
            'choices' => [
                'Oui' => 1,
                'Non' => 0,
            ],
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Local à sommeil',
            'mapped' => false,
            'attr' => [
                'class' => 'checkbox-group',
            ],
        ]);

        // Étude/Visite - Checkbox individuelles (Étude/Visite/Les deux)
        $builder->add('etudeVisiteValues', ChoiceType::class, [
            'choices' => [
                'Étude' => 1,
                'Visite' => 0,
            ],
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Type de dossier',
            'mapped' => false,
            'attr' => [
                'class' => 'checkbox-group',
            ],
        ]);

        // Charger les valeurs existantes pour localSommeil et etudeVisite
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var ?CommissionRegle $regle */
            $regle = $event->getData();
            $form = $event->getForm();

            if (!$regle) {
                return;
            }

            // Extraire les valeurs de localSommeil
            $localSommeilValues = [];
            foreach ($regle->getLocalSommeil() as $ls) {
                $localSommeilValues[] = $ls->getLocalSommeil();
            }

            $form->get('localSommeilValues')->setData($localSommeilValues);

            // Extraire les valeurs de etudeVisite
            $etudeVisiteValues = [];
            foreach ($regle->getEtudeVisite() as $ev) {
                $etudeVisiteValues[] = $ev->getEtudeVisite();
            }

            $form->get('etudeVisiteValues')->setData($etudeVisiteValues);
        });
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
