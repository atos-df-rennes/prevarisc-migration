<?php

namespace App\Form;

use App\Entity\CommissionType;
use App\Entity\Configurations;
use App\Entity\EtablissementType;
use App\Repository\EtablissementTypeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationType extends AbstractType
{
    /** @var EtablissementTypeRepository */
    private $etablissementTypeRepository;

    public function __construct(EtablissementTypeRepository $etablissementTypeRepository)
    {
        $this->etablissementTypeRepository = $etablissementTypeRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prevariscLocalSommeilTypes', EntityType::class, [
                'class' => EtablissementType::class,
                'choice_label' => 'libelle',
                'label' => 'Types de locaux de sommeil',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => ['size' => 10],
                'help' => 'Maintenez CTRL enfoncé pour sélectionner plusieurs types.',
            ]);

        $builder->get('prevariscLocalSommeilTypes')
            ->addModelTransformer(new CallbackTransformer(
                function (?string $localSommeilTypesAsString) {
                    if (null === $localSommeilTypesAsString) {
                        return [];
                    }

                    return array_map(function (string $localSommeilType) {
                        return $this->etablissementTypeRepository->find($localSommeilType);
                    }, explode(';',  $localSommeilTypesAsString));
                },
                /** @var array<EtablissementType> $etablissementsTypes */
                function (array $etablissementsTypes) {
                    if ([] === $etablissementsTypes) {
                        return '';
                    }

                    return implode(';', array_map(function (EtablissementType $etablissementType) {
                        return $etablissementType->getId();
                    }, $etablissementsTypes));
                }
            ));

        $builder->add('prevariscDebugEnabled', CheckboxType::class, [
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
        ->add('prevariscCommissionCommunaleType', EntityType::class, [
            'class' => CommissionType::class,
            'choice_label' => 'libelle',
            'label' => 'Type de commission communale',
            'required' => false,
            'placeholder' => 'Sélectionnez un type',
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
        ->add('prevariscDescriptifPersonnalise', CheckboxType::class, [
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
