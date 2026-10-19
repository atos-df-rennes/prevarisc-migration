<?php

namespace App\Form;

use App\Constant\Fonction;
use App\Entity\Fonction as FonctionEntity;
use App\Entity\UtilisateurCivilite;
use App\Entity\UtilisateurInformations;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('fonction', EntityType::class, [
                'class' => FonctionEntity::class,
                'choice_label' => 'libelle',
                'query_builder' => function (EntityRepository $entityRepository) use ($isEdit) {
                    $queryBuilder = $entityRepository->createQueryBuilder('f');
                    if (!$isEdit) {
                        $queryBuilder->where('f.id != :maireId')->setParameter('maireId', Fonction::MAIRE);
                    }

                    return $queryBuilder;
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
