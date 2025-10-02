<?php

namespace App\Form;

use App\Entity\Configurations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prevariscDebugEnabled', CheckboxType::class, [
                'label' => 'Debug activé',
                'required' => false,
            ])
            ->add('prevariscEnforceSecurity', CheckboxType::class, [
                'label' => 'Sécurité renforcée',
                'required' => false,
            ])
            ->add('prevariscPluginIgnkey', TextType::class, [
                'label' => 'Plugin IGN Key',
                'required' => false,
            ])
            ->add('prevariscLocalSommeilTypes', TextType::class, [
                'label' => 'Types de locaux de sommeil',
                'required' => false,
            ])
            ->add('prevariscCommissionCommunaleType', TextType::class, [
                'label' => 'Type de commission communale',
                'required' => false,
            ])
            ->add('prevariscRisquesNaturelsExemples', TextType::class, [
                'label' => 'Exemples de risques naturels',
                'required' => false,
            ])
            ->add('prevariscRisquesTechnologiquesExemples', TextType::class, [
                'label' => 'Exemples de risques technologiques',
                'required' => false,
            ])
            ->add('prevariscDashboardNextCommissionsDays', IntegerType::class, [
                'label' => 'Dashboard - Jours prochaines commissions',
                'required' => false,
            ])
            ->add('prevariscDashboardDossiersSansAvisDays', IntegerType::class, [
                'label' => 'Dashboard - Jours dossiers sans avis',
                'required' => false,
            ])
            ->add('prevariscDashboardCourrierSansReponseDays', IntegerType::class, [
                'label' => 'Dashboard - Jours courrier sans réponse',
                'required' => false,
            ])
            ->add('prevariscDateCommissionRelancePeriodicite', CheckboxType::class, [
                'label' => 'Périodicité relance date commission',
                'required' => false,
            ])
            ->add('prevariscDescriptifPersonnalise', TextType::class, [
                'label' => 'Descriptif personnalisé',
                'required' => false,
            ])
            ->add('prevariscUnitePeriodiciteAnnees', CheckboxType::class, [
                'label' => 'Unité de périodicité en années',
                'required' => false,
            ])
            ->add('prevariscDeactivatePlatau', CheckboxType::class, [
                'label' => 'Désactiver PLATAU',
                'required' => false,
            ])
            ->add('prevariscPlatauNoHealthcheck', CheckboxType::class, [
                'label' => 'PLATAU - Pas de healthcheck',
                'required' => false,
            ])
            ->add('prevariscDossiersMaxAAfficher', IntegerType::class, [
                'label' => 'Nombre maximum de dossiers à afficher',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configurations::class,
        ]);
    }
}
