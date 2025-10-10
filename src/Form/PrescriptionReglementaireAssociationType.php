<?php

namespace App\Form;

use App\Entity\PrescriptionReglementaireAssociation;
use App\Repository\PrescriptionArticleListeRepository;
use App\Repository\PrescriptionTexteListeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

    /**
     * Construit le formulaire avec des champs de choix pour les textes et articles.
     *
     * Utilise les choix récupérés depuis les repositories pour les options des champs.
     * Utilisation des ChoiceType avec un tableau array<string, int> pour hydrater les champs, car il n'y a pas de relations entre les entités.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choicesTextes = $this->prescriptionTexteListeRepository->findAllForChoices(true);
        $choicesArticles = $this->prescriptionArticleListeRepository->findAllForChoices(true);

        $builder
            ->add('texte', ChoiceType::class, [
                'label' => 'Texte',
                'choices' => $choicesTextes,
                'row_attr' => [
                    'class' => 'col-sm-5'
                ]
            ])
            ->add('article', ChoiceType::class, [
                'label' => 'Article',
                'choices' => $choicesArticles,
                'row_attr' => [
                    'class' => 'col-sm-5'
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
