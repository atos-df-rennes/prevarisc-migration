<?php

namespace App\Form;

use App\Entity\Groupe;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // @todo: Mettre des validations sur cet upload ?
            ->add('photo', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'label' => 'Login',
                'attr' => [
                    'placeholder' => 'Login de connexion ...',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Mot de passe',
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('groupe', EntityType::class, [
                'class' => Groupe::class,
                'choice_label' => 'libelle',
            ])
            ->add('ip', TextType::class, [
                'label' => 'Dernière IP authentifiée',
                'required' => false,
            ])
            ->add('failedLoginAttempts', IntegerType::class, [
                'label' => 'Connexions en échec',
                'empty_data' => 0,
                'required' => false,
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Utilisateur actif ?',
                'required' => false,
            ])
            ->add('informations', UtilisateurInformationsType::class, [
                'label' => "Informations de l'utilisateur",
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
