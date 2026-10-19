<?php

namespace App\Form\Dossier;

use App\Entity\DossierDocumentManquant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour un document manquant d'un dossier.
 */
final class DossierDocumentManquantType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Dossier incomplet le',
                'widget' => 'single_text', // Input HTML5 type="date"
                'required' => false,
                'attr' => [
                    'class' => 'date-incomplet',
                ],
            ])
            ->add('documentManquant', TextareaType::class, [
                'label' => 'Documents manquants',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'docManquant',
                    'placeholder' => 'Liste des documents manquants...',
                ],
            ])
            ->add('dateReception', DateType::class, [
                'label' => 'Date de réception des pièces',
                'widget' => 'single_text', // Input HTML5 type="date"
                'required' => false,
                'attr' => [
                    'class' => 'date-reception',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierDocumentManquant::class,
        ]);
    }
}
