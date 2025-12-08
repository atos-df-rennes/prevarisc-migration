<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Groupement;
use App\Entity\GroupementType as GroupementTypeEntity;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurInformations;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupementType extends AbstractType
{
    public const SCOPE_CREATE = 'create';
    public const SCOPE_GENERAL = 'general';
    public const SCOPE_AFFECTATIONS = 'affectations';
    public const SCOPE_COORDONNEES = 'coordonnees';
    public const SCOPE_CONTACTS = 'contacts';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $scopes = $options['scopes'];

        if (in_array(self::SCOPE_CREATE, $scopes) || in_array(self::SCOPE_GENERAL, $scopes)) {
            $builder
                ->add('libelle', TextType::class, [
                    'label' => 'Libellé du groupement',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Nom du groupement',
                    ],
                ])
                ->add('type', EntityType::class, [
                    'class' => GroupementTypeEntity::class,
                    'choice_label' => 'libelle',
                    'label' => 'Type',
                    'required' => true,
                    'placeholder' => 'Sélectionner un type',
                ])
            ;
        }

        if( in_array(self::SCOPE_GENERAL, $scopes) )  {
            $builder->add('communes', EntityType::class, [
                'class' => Commune::class,
                'choice_label' => function (Commune $commune): string {
                    return $commune->getId().' '.$commune->getLibelle();
                },
                'label' => 'Villes du groupement',
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'size' => 20,
                    'class' => 'form-control',
                ],
            ]);
        }

        if(in_array(self::SCOPE_AFFECTATIONS, $scopes) ) {
            $builder
                ->add('preventionnistes', EntityType::class, [
                    'class' => Utilisateur::class,
                    'choice_label' => function (Utilisateur $utilisateur): string {
                        $info = $utilisateur->getInformations();

                        return $info instanceof UtilisateurInformations ? ($info->getGrade() . ' ' . $info->getNom() . ' ' . $info->getPrenom()) : $utilisateur->getUsername();
                    },
                    'label' => 'Préventionnistes affectés',
                    'multiple' => true,
                    'required' => false,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->innerJoin('u.informations', 'i')
                            ->innerJoin('i.fonction', 'f')
                            ->where('i.id = :fonction')
                            ->setParameter('fonction', 13)
                            ->orderBy('u.id', 'ASC');
                    },
                ])
                ->add('reaffectationAutomatique', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                ])
            ;
        }

        if(in_array(self::SCOPE_COORDONNEES, $scopes) ) {
            $builder->add('informations', UtilisateurInformationsType::class, [
                'label' => 'Coordonnées du groupement',
                'required' => false,
                'scopes' => ['adresse', 'societe']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupement::class,
            'scopes' => [],
        ]);
    }
}
