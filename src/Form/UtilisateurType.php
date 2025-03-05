<?php

namespace App\Form;

use App\Entity\Commission;
use App\Entity\Commune;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\Utilisateur;
use App\Service\Authentification;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    /** @var bool */
    private $enforceSecurity;

    /** @var Authentification */
    private $authentification;

    public function __construct(bool $enforceSecurity, Authentification $authentification)
    {
        $this->enforceSecurity = $enforceSecurity;
        $this->authentification = $authentification;
    }

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
                'help' => $this->enforceSecurity ? 'Le mot de passe doit contenir au moins 8 caractères dont 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial.' : null,
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
            ->add('commune', EntityType::class, [
                'class' => Commune::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Définir une commune ...',
            ])
            ->add('commissions', EntityType::class, [
                'class' => Commission::class,
                'choice_label' => 'libelle',
                'required' => false,
                'multiple' => true,
                'group_by' => function (Commission $choice) {
                    return $choice->getType()->getLibelle();
                },
            ])
            ->add('groupements', EntityType::class, [
                'class' => Groupement::class,
                'choice_label' => 'libelle',
                'required' => false,
                'multiple' => true,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('g')
                        ->addSelect('gt')
                        ->join('g.type', 'gt')
                        ->orderBy('gt.id, g.libelle')
                    ;
                },
                'group_by' => function (Groupement $choice) {
                    return $choice->getType()->getLibelle();
                },
            ])
        ;

        if ($this->authentification->ssoEnabled()) {
            $builder->add('sso', CheckboxType::class, [
                'label' => 'Connexion via LDAP/CAS/NTLM ?',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
