<?php

namespace App\Form;

use App\Entity\UtilisateurInformations;
use App\Repository\UtilisateurInformationsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddExistingContactsType extends AbstractType
{
    public function __construct(private UtilisateurInformationsRepository $utilisateurInformationsRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contacts', EntityType::class, [
                'class' => UtilisateurInformations::class,
                'choice_loader' => new CallbackChoiceLoader(function () use ($options) {
                    // Retourne une liste vide si is_post est false, ce qui évite de charger tous les contacts lors de l'affichage initial du formulaire
                    if (array_key_exists('is_post', $options) && false === $options['is_post']) {
                        return [];
                    }

                    // Retourne tous les contacts non associés à un utilisateur
                    return $this->utilisateurInformationsRepository->createNotInUserQueryBuilder()->getQuery()->getResult();
                }),
                'choice_label' => fn (UtilisateurInformations $contact): string => $contact->getPrenom().' '.$contact->getNom(),
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
            'is_post' => false,
        ]);
    }
}
