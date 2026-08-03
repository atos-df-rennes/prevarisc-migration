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
    public function __construct(private readonly int $commissionCommunaleType)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Commission $commission */
        $commission = $options['commission'];
        $isCommunale = $commission->getType() && $this->commissionCommunaleType === $commission->getType()->getId();

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
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->orderBy('c.libelle', 'ASC'),
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
                'group_by' => fn (Groupement $groupement): ?string => $groupement->getType() instanceof GroupementType ? $groupement->getType()->getLibelle() : 'Autre',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('g')
                    ->leftJoin('g.type', 't')
                    ->addSelect('t')
                    ->orderBy('t.id', 'ASC')
                    ->addOrderBy('g.libelle', 'ASC'),
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
                'data-select-all-target' => 'categories',
            ],
            'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                ->orderBy('c.libelle', 'ASC'),
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
            'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
                ->orderBy('t.libelle', 'ASC'),
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
            'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                ->orderBy('c.libelle', 'ASC'),
        ]);

        // Local Sommeil et Étude/Visite : champs non mappés, pré-remplis via PRE_SET_DATA,
        // synchronisés vers la collection de l'entité via POST_SUBMIT.
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, $this->onPreSetData(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->onPostSubmit(...))
        ;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'commission_regle';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommissionRegle::class,
        ]);

        $resolver->setRequired('commission');
        $resolver->setAllowedTypes('commission', Commission::class);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $regle = $event->getData();

        $localSommeilValues = [];
        $etudeVisiteValues = [];

        if ($regle instanceof CommissionRegle) {
            $reglesLocalSommeil = $regle->getLocalSommeil();
            foreach ($reglesLocalSommeil as $regleLocalSommeil) {
                $localSommeilValues[] = $regleLocalSommeil->getLocalSommeil();
            }

            $reglesEtudeVisite = $regle->getEtudeVisite();
            foreach ($reglesEtudeVisite as $regleEtudeVisite) {
                $etudeVisiteValues[] = $regleEtudeVisite->getEtudeVisite();
            }
        }

        $form->add('localSommeil', ChoiceType::class, [
            'choices' => ['Oui' => 1, 'Non' => 0],
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'mapped' => false,
            'label' => 'Local à sommeil',
            'data' => $localSommeilValues,
        ]);

        $form->add('etudeVisite', ChoiceType::class, [
            'choices' => ['Étude' => 1, 'Visite' => 0],
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'mapped' => false,
            'label' => 'Type de dossier',
            'data' => $etudeVisiteValues,
        ]);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $regle = $form->getData();

        if (!$regle instanceof CommissionRegle) {
            return;
        }

        /** @var array<int> $submittedLocalSommeil */
        $submittedLocalSommeil = is_array($form->get('localSommeil')->getData()) ? $form->get('localSommeil')->getData() : [];
        $this->updateLocalSommeil($regle, $submittedLocalSommeil);

        /** @var array<int> $submittedEtudeVisite */
        $submittedEtudeVisite = is_array($form->get('etudeVisite')->getData()) ? $form->get('etudeVisite')->getData() : [];
        $this->updateEtudeVisite($regle, $submittedEtudeVisite);
    }

    /**
     * Ajoute les soumissions non existantes en base
     * Supprime celles en base non soumises.
     *
     * @param array<int> $submittedLocalSommeil
     */
    private function updateLocalSommeil(CommissionRegle $regle, array $submittedLocalSommeil): void
    {
        $existingLocalSommeil = [];

        $reglesLocalSommeil = $regle->getLocalSommeil();
        foreach ($reglesLocalSommeil as $regleLocalSommeil) {
            $existingLocalSommeil[$regleLocalSommeil->getLocalSommeil()] = $regleLocalSommeil;
        }

        // Si non existant, ajoute l'entité et retire l'entrée du tableau des existants
        // Cela permet de ne conserver que ceux existants n'étant pas soumis => à supprimer
        foreach ($submittedLocalSommeil as $value) {
            if (!isset($existingLocalSommeil[$value])) {
                $entity = new CommissionRegleLocalSommeil();
                $entity->setLocalSommeil($value);
                $regle->addLocalSommeil($entity);
            }

            unset($existingLocalSommeil[$value]);
        }

        foreach ($existingLocalSommeil as $toRemove) {
            $regle->removeLocalSommeil($toRemove);
        }
    }

    /**
     * @param array<int> $submittedEtudeVisite
     *
     * @see CommissionRegleType::updateLocalSommeil()
     */
    private function updateEtudeVisite(CommissionRegle $regle, array $submittedEtudeVisite): void
    {
        $existingEtudeVisite = [];

        $reglesEtudeVisite = $regle->getEtudeVisite();
        foreach ($reglesEtudeVisite as $regleEtudeVisite) {
            $existingEtudeVisite[$regleEtudeVisite->getEtudeVisite()] = $regleEtudeVisite;
        }

        foreach ($submittedEtudeVisite as $value) {
            if (!isset($existingEtudeVisite[$value])) {
                $entity = new CommissionRegleEtudeVisite();
                $entity->setEtudeVisite($value);
                $regle->addEtudeVisite($entity);
            }

            unset($existingEtudeVisite[$value]);
        }

        foreach ($existingEtudeVisite as $toRemove) {
            $regle->removeEtudeVisite($toRemove);
        }
    }
}
