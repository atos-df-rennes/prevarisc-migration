<?php

namespace App\Form;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionReglementaire;
use App\Entity\PrescriptionReglementaireAssociation;
use App\Entity\PrescriptionTexteListe;
use App\Repository\PrescriptionArticleListeRepository;
use App\Repository\PrescriptionTexteListeRepository;
use App\Service\Prescriptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrescriptionReglementaireAssociationType extends AbstractType
{
    /** @var PrescriptionArticleListeRepository */
    private $prescriptionArticleListeRepository;

    /** @var PrescriptionTexteListeRepository */
    private $prescriptionTexteListeRepository;

    public function __construct(
        PrescriptionArticleListeRepository $prescriptionArticleListeRepository,
        PrescriptionTexteListeRepository $prescriptionTexteListeRepository
    ) {
        $this->prescriptionArticleListeRepository = $prescriptionArticleListeRepository;
        $this->prescriptionTexteListeRepository = $prescriptionTexteListeRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choicesTextes = $this->prescriptionTexteListeRepository->findAllForChoices(true);
        $choicesArticles = $this->prescriptionArticleListeRepository->findAllForChoices( true);

        $builder
            ->add('numero', HiddenType::class)
            ->add('texte', ChoiceType::class, [
                'label' => 'Texte',
                'choices' => $choicesTextes,
                'row_attr' => [
                    'class' => 'col-sm-6'
                ]
            ])
            ->add('article', ChoiceType::class, [
                'label' => 'Article',
                'choices' => $choicesArticles,
                'row_attr' => [
                    'class' => 'col-sm-6'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrescriptionReglementaireAssociation::class,
        ]);
    }
}
