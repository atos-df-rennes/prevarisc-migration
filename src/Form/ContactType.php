<?php

namespace App\Form;

use App\Entity\Fonction;
use App\Entity\UtilisateurCivilite;
use App\Entity\UtilisateurInformations;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
            ])
            ->add('societe', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('civilite', EntityType::class, [
                'class' => UtilisateurCivilite::class,
                'choice_label' => 'libelle',
                'required' => false,
            ])
            ->add('nom', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 50]),
                ],
            ])
            ->add('prenom', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 50]),
                ],
            ])
            ->add('telephoneFixe', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 20]),
                ],
            ])
            ->add('telephoneFax', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 20]),
                ],
            ])
            ->add('telephonePortable', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 20]),
                ],
            ])
            ->add('mail', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Email(),
                    new Assert\Length(['max' => 50]),
                ],
            ])
            ->add('siteWeb', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('numeroAdresse', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 10]),
                ],
            ])
            ->add('rueAdresse', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('codePostalAdresse', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 5]),
                ],
            ])
            ->add('villeAdresse', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('observations', null, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilisateurInformations::class,
        ]);
    }
}
