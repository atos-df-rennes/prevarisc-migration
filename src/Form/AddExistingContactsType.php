<?php

namespace App\Form;

use App\Entity\UtilisateurInformations;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddExistingContactsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contacts', EntityType::class, [
                'class' => UtilisateurInformations::class,
                'choice_label' => function (UtilisateurInformations $contact) {
                    return $contact->getPrenom() . ' ' . $contact->getNom();
                },
                'multiple' => true,
                'label' => 'Rechercher des contacts',
                'mapped' => false,
                'attr' => ['class' => 'tomselect'],
                'help' => 'Recherchez et sélectionnez un ou plusieurs contacts. Saisissez au moins 3 caractères pour lancer la recherche.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
