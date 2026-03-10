<?php

namespace App\Form;

use App\Entity\Fonction;
use App\Entity\UtilisateurCivilite;
use App\Entity\UtilisateurInformations;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
            ->add('societe', null, ['required' => false])
            ->add('civilite', EntityType::class, [
                'class' => UtilisateurCivilite::class,
                'choice_label' => 'libelle',
                'required' => false,
            ])
            ->add('nom', null, ['required' => false])
            ->add('prenom', null, ['required' => false])
            ->add('telephoneFixe', null, ['required' => false])
            ->add('telephoneFax', null, ['required' => false])
            ->add('telephonePortable', null, ['required' => false])
            ->add('mail', null, ['required' => false])
            ->add('siteWeb', null, ['required' => false])
            ->add('numeroAdresse', null, ['required' => false])
            ->add('rueAdresse', null, ['required' => false])
            ->add('codePostalAdresse', null, ['required' => false])
            ->add('villeAdresse', null, ['required' => false])
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
