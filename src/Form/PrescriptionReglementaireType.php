<?php

namespace App\Form;

use App\Entity\PrescriptionReglementaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrescriptionReglementaireType extends AbstractType
{
    /**
     * Configure le formulaire pour la gestion des prescriptions réglementaires.
     *
     * @param FormBuilderInterface $builder instance de constructeur de formulaire pour définir les champs du formulaire
     * @param array                $options options supplémentaires passées au formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Ajouter cette prescription pour les',
                'choices' => [
                    'Etudes' => 1,
                    'Visites' => 2
                ]
            ])
            ->add('visible', ChoiceType::class, [
                'label' => 'Ajouter par défaut à la création d\'un nouveau dossier',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ]
            ])
            ->add('associations', CollectionType::class, [
                'entry_type' => PrescriptionReglementaireAssociationType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
            ])
            ->add('libelle', TextareaType::class, [
                'label' => 'Contenu',
            ])
        ;
    }

    /**
     * Configure les options pour ce formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur d'options à configurer
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionReglementaire::class,
        ]);
    }
}
