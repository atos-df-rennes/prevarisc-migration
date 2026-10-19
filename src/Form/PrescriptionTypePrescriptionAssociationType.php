<?php

namespace App\Form;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionTexteListe;
use App\Entity\PrescriptionTypeAssociation;
use App\Repository\PrescriptionArticleListeRepository;
use App\Repository\PrescriptionTexteListeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PrescriptionTypePrescriptionAssociationType extends AbstractType
{
    public function __construct(private readonly PrescriptionArticleListeRepository $prescriptionArticleListeRepository, private readonly PrescriptionTexteListeRepository $prescriptionTexteListeRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('texte', EntityType::class, [
                'label' => 'Texte',
                'class' => PrescriptionTexteListe::class,
                'choices' => $this->prescriptionTexteListeRepository->findAllTextes(true),
                'choice_label' => 'libelle',
                'choice_value' => 'id',
                'row_attr' => [
                    'class' => 'col-sm-5'
                ]
            ])
            ->add('article', EntityType::class, [
                'label' => 'Article',
                'class' => PrescriptionArticleListe::class,
                'choices' => $this->prescriptionArticleListeRepository->findAllArticles(true),
                'choice_label' => 'libelle',
                'choice_value' => 'id',
                'row_attr' => [
                    'class' => 'col-sm-5'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionTypeAssociation::class,
        ]);
    }
}
