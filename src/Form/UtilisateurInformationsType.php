<?php

namespace App\Form;

use App\Entity\Utilisateurinformations;
use App\Entity\Fonction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurInformationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('nom', TextType::class, [
                'required' => false,
            ])
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
            ])
            ->add('grade', TextType::class, [
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateurinformations::class,
        ]);
    }
}
