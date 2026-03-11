<?php

namespace App\Form;

use App\Entity\Commission;
use App\Entity\CommissionMembre;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\Groupement;
use App\Entity\GroupementType;
use App\Entity\IghClasse;
use App\Entity\UtilisateurInformations;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommissionMembreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Commission $commission */
        $commission = $options['commission'];

        $builder->add('libelle', TextType::class, [
            'label' => 'Libellé',
            'required' => false,
            'attr' => ['placeholder' => 'Nom du membre'],
        ]);

        $builder->add('presence', ChoiceType::class, [
            'label' => 'Présence',
            'choices' => [
                'Membre Obligatoire' => 0,
                'Membre Facultatif' => 1,
                'Membre Pour information' => 2,
            ],
        ]);

        // Champ non-mappé pour déterminer le type de membre (groupement / contact / commune)
        $builder->add('typeMembre', ChoiceType::class, [
            'label' => 'Type de membre',
            'mapped' => false,
            'choices' => [
                'Groupement de commune' => 1,
                'Contact de la commission' => 2,
                'Commune' => 3,
            ],
        ]);

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

        // Contacts de la commission (filtrés sur cette commission uniquement)
        $contactIds = $commission->getContacts()
            ->map(static function (UtilisateurInformations $c): ?int { return $c->getId(); })
            ->toArray();

        $builder->add('utilisateurInformations', EntityType::class, [
            'class' => UtilisateurInformations::class,
            'choice_label' => function (UtilisateurInformations $ui): string {
                return trim($ui->getNom().' '.$ui->getPrenom());
            },
            'placeholder' => 'Sélectionnez un contact',
            'required' => false,
            'label' => 'Contact',
            'attr' => [
                'class' => 'tom-select',
                'data-placeholder' => 'Sélectionnez un contact',
            ],
            'query_builder' => function (EntityRepository $er) use ($contactIds) {
                $qb = $er->createQueryBuilder('ui')
                    ->orderBy('ui.nom', 'ASC')
                    ->addOrderBy('ui.prenom', 'ASC');

                if ([] !== $contactIds) {
                    $qb->where('ui.id IN (:ids)')->setParameter('ids', $contactIds);
                } else {
                    // Aucun contact : retourne un résultat vide
                    $qb->where('1 = 0');
                }

                return $qb;
            },
        ]);

        // Catégories ERP - checkboxes
        $builder->add('categories', EntityType::class, [
            'class' => EtablissementCategorie::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Catégories ERP',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')->orderBy('c.libelle', 'ASC');
            },
        ]);

        // Activités ERP - EntityType mappé sur CommissionMembre::types, groupé par type ERP
        $builder->add('types', EntityType::class, [
            'class' => EtablissementActivite::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Types d\'activité ERP',
            'group_by' => function (EtablissementActivite $activite): string {
                $type = $activite->getType();

                return null !== $type ? (string) $type->getLibelle() : 'Autres';
            },
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('a')
                    ->leftJoin('a.type', 't')
                    ->addSelect('t')
                    ->orderBy('t.libelle', 'ASC')
                    ->addOrderBy('a.libelle', 'ASC');
            },
        ]);

        $builder->add('classes', EntityType::class, [
            'class' => IghClasse::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Classes IGH',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')->orderBy('c.libelle', 'ASC');
            },
        ]);

        // Types de dossier - checkboxes
        $builder->add('typesDossier', EntityType::class, [
            'class' => DossierType::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Types de dossier',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('dt')->orderBy('dt.libelle', 'ASC');
            },
        ]);

        // Natures de dossier - checkboxes (imbriquées par type dans le template)
        $builder->add('naturesDossier', EntityType::class, [
            'class' => DossierNatureListe::class,
            'choice_label' => 'libelle',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'Natures de dossier',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('n')
                    ->leftJoin('n.type', 'dt')
                    ->addSelect('dt')
                    ->orderBy('dt.libelle', 'ASC')
                    ->addOrderBy('n.ordre', 'ASC')
                    ->addOrderBy('n.libelle', 'ASC');
            },
        ]);

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $membre = $event->getData();

        // Détermine le type de membre initial
        $typeMembre = 3; // Commune par défaut
        if ($membre instanceof CommissionMembre) {
            if (null !== $membre->getGroupement()) {
                $typeMembre = 1;
            } elseif (null !== $membre->getUtilisateurInformations()) {
                $typeMembre = 2;
            }
        }

        $form->add('typeMembre', ChoiceType::class, [
            'label' => 'Type de membre',
            'mapped' => false,
            'data' => $typeMembre,
            'choices' => [
                'Groupement de commune' => 1,
                'Contact de la commission' => 2,
                'Commune' => 3,
            ],
        ]);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $membre = $form->getData();

        if (!$membre instanceof CommissionMembre) {
            return;
        }

        // Synchronise groupement selon le type de membre
        $typeMembre = is_int($form->get('typeMembre')->getData()) ? $form->get('typeMembre')->getData() : 3;
        if (1 !== $typeMembre) {
            $membre->setGroupement(null);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'commission_membre';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommissionMembre::class,
        ]);

        $resolver->setRequired('commission');
        $resolver->setAllowedTypes('commission', Commission::class);
    }
}
