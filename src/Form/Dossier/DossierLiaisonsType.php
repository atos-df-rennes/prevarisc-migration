<?php

namespace App\Form\Dossier;

use App\Entity\Dossier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierLiaisonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('dossiersToAdd', EntityType::class, [
            'label' => false,
            'class' => Dossier::class,
            'choices' => $options['available_dossiers'],
            'choice_label' => function (Dossier $dossier): string {
                $nature = $dossier->getNature() ? (string) $dossier->getNature()->getLibelle() : 'Sans nature';
                $date = $dossier->getDateInsert() ? $dossier->getDateInsert()->format('d/m/Y') : '';

                return sprintf('%s - %s', $nature, $date);
            },
            'group_by' => function (Dossier $dossier): string {
                return $dossier->getType() ? (string) $dossier->getType()->getLibelle() : 'Autres';
            },            'multiple' => true,
            'expanded' => true,
            'mapped' => false,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('available_dossiers')
            ->setAllowedTypes('available_dossiers', 'iterable');
    }
}
