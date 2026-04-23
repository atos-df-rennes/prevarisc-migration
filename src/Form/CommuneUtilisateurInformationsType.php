<?php

namespace App\Form;

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
use Symfony\Component\Validator\Constraints as Assert;

class CommuneUtilisateurInformationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('civilite', EntityType::class, [
                'label' => false,
                'class' => UtilisateurCivilite::class,
                'choice_label' => 'libelle',
                'required' => false,
            ])
            ->add('nom', TextType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('prenom', TextType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('telephoneFixe', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('telephoneFax', TelType::class, [
                'label' => 'Fax',
                'required' => false,
            ])
            ->add('telephonePortable', TelType::class, [
                'label' => 'Portable',
                'required' => false,
            ])
            ->add('mail', EmailType::class, [
                'label' => 'Courriel',
                'required' => false,
                'constraints' => [
                    new Assert\Email(),
                ],
            ])
            ->add('siteWeb', UrlType::class, [
                'required' => false,
            ])
            ->add('numeroAdresse', TextType::class, [
                'label' => 'Numéro',
                'required' => false,
            ])
            ->add('rueAdresse', TextType::class, [
                'label' => 'Rue',
                'required' => false,
            ])
            ->add('codePostalAdresse', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
            ])
            ->add('villeAdresse', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('observations', TextareaType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilisateurInformations::class,
        ]);
    }
}
