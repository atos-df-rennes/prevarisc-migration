<?php

namespace App\Form;

use App\Constant\TypePrescription;
use App\Entity\PrescriptionDossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PrescriptionDossierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Rappels réglementaires' => TypePrescription::REGLEMENTAIRE,
                    "Prescriptions liées à l'exploitation" => TypePrescription::EXPLOITATION,
                    "Recommandations liées à l'amélioration" => TypePrescription::AMELIORATION,
                ],
            ])
            ->add('libelle', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['rows' => 10],
                'constraints' => [
                    new NotBlank(message: 'Le contenu de la prescription est obligatoire.'),
                ],
            ])
            ->add('associations', CollectionType::class, [
                'entry_type' => PrescriptionDossierAssociationType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Vous devez ajouter au moins une association (texte/article).'),
                ],
                'error_bubbling' => false,
            ])
            ->add('dateLevee', DateType::class, [
                'label' => 'Date de levée',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('justificatif', TextareaType::class, [
                'label' => 'Justification de levée',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionDossier::class,
        ]);
    }
}
