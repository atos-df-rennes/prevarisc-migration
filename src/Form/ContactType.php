<?php

namespace App\Form;

use App\Entity\Fonction;
use App\Entity\UtilisateurCivilite;
use App\Entity\UtilisateurInformations;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    /** ID de la fonction "Maire" en base (ID_FONCTION = 2). */
    const FONCTION_MAIRE_ID = 2;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
                'query_builder' => function (EntityRepository $er) use ($isEdit) {
                    $qb = $er->createQueryBuilder('f')->orderBy('f.libelle', 'ASC');
                    if (!$isEdit) {
                        $qb->where('f.id != :maireId')->setParameter('maireId', self::FONCTION_MAIRE_ID);
                    }

                    return $qb;
                },
            ])
            ->add('societe', null, [
                'required' => false,
            ])
            ->add('civilite', EntityType::class, [
                'class' => UtilisateurCivilite::class,
                'choice_label' => 'libelle',
                'required' => false,
            ])
            ->add('nom', null, [
                'required' => false,
            ])
            ->add('prenom', null, [
                'required' => false,
            ])
            ->add('telephoneFixe', null, [
                'required' => false,
            ])
            ->add('telephoneFax', null, [
                'required' => false,
            ])
            ->add('telephonePortable', null, [
                'required' => false,
            ])
            ->add('mail', null, [
                'required' => false,
                'constraints' => [
                    new Assert\Email(),
                ],
            ])
            ->add('siteWeb', null, [
                'required' => false,
            ])
            ->add('numeroAdresse', null, [
                'required' => false,
            ])
            ->add('rueAdresse', null, [
                'required' => false,
            ])
            ->add('codePostalAdresse', null, [
                'required' => false,
            ])
            ->add('villeAdresse', null, [
                'required' => false,
            ])
            ->add('observations', null, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilisateurInformations::class,
            'is_edit' => false,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
