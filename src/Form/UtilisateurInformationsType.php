<?php

namespace App\Form;

use App\Entity\Fonction;
use App\Entity\UtilisateurCivilite;
use App\Entity\UtilisateurInformations;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurInformationsType extends AbstractType
{
    public const SCOPE_GRADE = 'grade';

    public const SCOPE_FONCTION = 'fonction';

    public const SCOPE_ADRESSE = 'adresse';

    public const SCOPE_SOCIETE = 'societe';

    /**
     * Construit le formulaire en ajoutant différents champs selon les options et portées fournies.
     *
     * @param FormBuilderInterface $builder instance utilisée pour ajouter les champs au formulaire
     * @param array                $options options passées lors de la création du formulaire, notamment les portées spécifiques
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('civilite', EntityType::class, [
                'class' => UtilisateurCivilite::class,
                'choice_label' => 'libelle',
                'required' => false,
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('nom', TextType::class, [
                'required' => false,
            ])

            ->add('telephoneFixe', TelType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Téléphone fixe ...',
                ],
            ])
            ->add('telephonePortable', TelType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Portable ...',
                ],
            ])
            ->add('telephoneFax', TelType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Fax ...',
                ],
            ])
            ->add('mail', EmailType::class, [
                'label' => 'Courriel',
                'required' => false,
            ])
            ->add('siteWeb', UrlType::class, [
                'required' => false,
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
        ;

        if (in_array(self::SCOPE_FONCTION, $options['scopes'])) {
            $builder->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
            ]);
        }

        if (in_array(self::SCOPE_GRADE, $options['scopes'])) {
            $builder->add('grade', TextType::class, [
                'required' => false,
            ]);
        }

        if (in_array(self::SCOPE_ADRESSE, $options['scopes'])) {
            $builder
                ->add('numeroAdresse')
                ->add('rueAdresse')
                ->add('codePostalAdresse')
                ->add('villeAdresse')
            ;
        }

        if (in_array(self::SCOPE_SOCIETE, $options['scopes'])) {
            $builder->add('societe');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilisateurInformations::class,
            'scopes' => [],
        ]);
    }
}
