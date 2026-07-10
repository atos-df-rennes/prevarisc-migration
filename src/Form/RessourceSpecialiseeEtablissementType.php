<?php

namespace App\Form;

use App\Entity\Classement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\IghClasse;
use App\Repository\EtablissementActiviteRepository;
use App\Repository\GenreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RessourceSpecialiseeEtablissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextareaType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Laissez vide pour donner un nom par défaut',
                ],
            ])
            ->add('genre', EntityType::class, [
                'mapped' => false,
                'class' => Genre::class,
                'query_builder' => fn (GenreRepository $genreRepository) => $genreRepository->createQueryBuilder('g')
                    ->where('g.libelle != :type')
                    ->setParameter('type', 'Site'),
                'choice_label' => 'libelle',
            ])
            ->add('typesActivites', EntityType::class, [
                'label' => "Types d'activité",
                'help' => 'CTRL + clic pour sélectionner plusieurs.',
                'required' => false,
                'mapped' => false,
                'class' => EtablissementActivite::class,
                'query_builder' => fn (EtablissementActiviteRepository $etablissementActiviteRepository) => $etablissementActiviteRepository->createQueryBuilder('ea')
                    ->orderBy('ea.type', 'ASC'),
                'group_by' => function (EtablissementActivite $etablissementActivite): ?string {
                    $etablissementActiviteType = $etablissementActivite->getType();

                    return null !== $etablissementActiviteType ? $etablissementActiviteType->getLibelle() : null;
                },
                'choice_label' => 'libelle',
                'multiple' => true,
                'row_attr' => ['class' => 'champs champ_types']
            ])
            ->add('categories', EntityType::class, [
                'label' => 'Catégories',
                'required' => false,
                'class' => EtablissementCategorie::class,
                'choice_label' => 'libelle',
                'multiple' => true,
                'row_attr' => ['class' => 'champs champ_categorie']
            ])
            ->add('familles', EntityType::class, [
                'required' => false,
                'class' => Famille::class,
                'choice_label' => 'libelle',
                'multiple' => true,
                'row_attr' => ['class' => 'champs champ_famille']
            ])
            ->add('classes', EntityType::class, [
                'required' => false,
                'class' => IghClasse::class,
                'choice_label' => 'libelle',
                'multiple' => true,
                'row_attr' => ['class' => 'champs champ_classe']
            ])
            ->add('classements', EntityType::class, [
                'required' => false,
                'class' => Classement::class,
                'choice_label' => 'libelle',
                'multiple' => true,
                'row_attr' => ['class' => 'champs champ_classement']
            ])
            ->add('commissions', ChoiceType::class, [
                'choices' => [
                    "Ignorer les commissions compétentes de l'utilisateur" => 0,
                    "Des commissions compétentes de l'utilisateur" => 1,
                ],
                'row_attr' => ['class' => 'champs champ_commission']
            ])
            ->add('groupements', ChoiceType::class, [
                'choices' => [
                    "Ignorer les groupements de l'utilisateur" => 0,
                    "Sur les groupements de l'utilisateur" => 1,
                ],
                'row_attr' => ['class' => 'champs champ_adresse']
            ])
            ->add('commune', ChoiceType::class, [
                'choices' => [
                    "Ignorer la commune de l'utilisateur" => 0,
                    "Sur la commune de l'utilisateur" => 1,
                ],
                'row_attr' => ['class' => 'champs champ_adresse']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
