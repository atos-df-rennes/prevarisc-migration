<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Groupement;
use App\Entity\GroupementType as GroupementTypeEntity;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurInformations;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Configure le formulaire à construire selon les options fournies.
     *
     * Cette méthode permet d'ajouter dynamiquement des champs au formulaire en fonction
     * des "scopes" spécifiés dans les options. Elle gère les cas suivants :
     * - Ajout d'un champ pour le libellé et le type du groupement (si les scopes incluent CREATE ou GENERAL).
     * - Ajout d'un champ pour sélectionner les communes associées au groupement (si le scope inclut GENERAL).
     * - Ajout d'un champ pour l'affectation des préventionnistes associés (si le scope inclut AFFECTATIONS).
     * - Ajout d'un champ pour les coordonnées du groupement, incluant une sous-configuration pour 'adresse' et 'societe' (si le scope inclut COORDONNEES).
     *
     * @param FormBuilderInterface $builder le constructeur de formulaire
     * @param array<string,mixed>  $options les options permettant de configurer le formulaire, dont :
     *                                      - 'scopes': tableau obligatoire qui détermine les sections à inclure dans le formulaire
     *
     * @throws \InvalidArgumentException si l'option "scopes" est absente ou n'est pas un tableau
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!array_key_exists('scopes', $options) || !is_array($options['scopes'])) {
            throw new \InvalidArgumentException('L\'option "scopes" doit être un tableau.');
        }

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

        if (in_array(self::SCOPE_GENERAL, $scopes)) {
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

        if (in_array(self::SCOPE_AFFECTATIONS, $scopes)) {
            $builder
                ->add('preventionnistes', EntityType::class, [
                    'class' => Utilisateur::class,
                    'choice_label' => function (Utilisateur $utilisateur): string {
                        $info = $utilisateur->getInformations();

                        return $info instanceof UtilisateurInformations ? ($info->getGrade().' '.$info->getNom().' '.$info->getPrenom()) : $utilisateur->getUsername();
                    },
                    'label' => 'Préventionnistes affectés',
                    'multiple' => true,
                    'required' => false,
                    'query_builder' => function (UtilisateurRepository $repository): QueryBuilder {
                        return $repository->createPreventionnistesQueryBuilder();
                    },
                ])
                ->add('reaffectationAutomatique', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                ])
            ;
        }

        if (in_array(self::SCOPE_COORDONNEES, $scopes)) {
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
