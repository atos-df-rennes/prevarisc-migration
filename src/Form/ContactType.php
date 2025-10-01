<?php

namespace App\Form;

use App\Entity\Fonction;
use App\Entity\UtilisateurCivilite;
use App\Entity\UtilisateurInformations;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
            ])
            ->add('societe')
            ->add('civilite', EntityType::class, [
                'class' => UtilisateurCivilite::class,
                'choice_label' => 'libelle',
                'required' => false,
            ])
            ->add('nom')
            ->add('prenom')
            ->add('telephoneFixe')
            ->add('telephoneFax')
            ->add('telephonePortable')
            ->add('mail')
            ->add('siteWeb')
            ->add('numeroAdresse')
            ->add('rueAdresse')
            ->add('codePostalAdresse')
            ->add('villeAdresse')
            ->add('observations')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilisateurInformations::class,
        ]);
    }
}
