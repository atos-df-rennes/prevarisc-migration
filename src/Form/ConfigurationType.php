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
            ->add('prevariscDebugEnabled', CheckboxType::class, [
                'label' => 'Mode debug',
                'required' => false,
            ])
            ->add('prevariscEnforceSecurity', CheckboxType::class, [
                'label' => 'Sécurité renforcée',
                'required' => false,
            ])
        ;

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

        $builder
            ->get('prevariscLocalSommeilTypes')
            ->addModelTransformer(new CallbackTransformer(
                function (?string $localSommeilTypesAsString): array {
                    if (null === $localSommeilTypesAsString) {
                        return [];
                    }

                    // Convertit les identifiants en entités
                    return array_map(function (string $localSommeilType) {
                        return $this->etablissementTypeRepository->find($localSommeilType);
                    }, explode(';', $localSommeilTypesAsString));
                },
                /** @var EtablissementType[] $etablissementsTypes */
                function (array $etablissementsTypes): string {
                    if ([] === $etablissementsTypes) {
                        return '';
                    }

                    // Convertit les entités en identifiants
                    return implode(';', array_map(function ($etablissementType): int {
                        if (!$etablissementType instanceof EtablissementType) {
                            throw new \InvalidArgumentException("Le type d'établissement n'est pas valide.");
                        }

                        $id = $etablissementType->getId();

                        if (null === $id) {
                            throw new \DomainException("L'identifiant du type d'établissement ne peut pas être null.");
                        }

                        return $id;
                    }, $etablissementsTypes));
                }
            ));

        $builder
            ->add('prevariscPluginIgnkey', TextType::class, [
                'label' => 'Clés IGN',
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
            ->add('prevariscDossiersMaxAAfficher', IntegerType::class, [
                'label' => 'Nombre maximum de dossiers à afficher',
                'required' => false,
            ])
            ->add('prevariscDateCommissionRelancePeriodicite', CheckboxType::class, [
                'label' => 'Relancer la périodicité à partir de la date de commission',
                'help' => 'Uniquement pour les groupes de visite',
                'required' => false,
            ])
            ->add('prevariscDescriptifPersonnalise', CheckboxType::class, [
                'label' => "Descriptif d'établissement personnalisé",
                'required' => false,
            ])
            ->add('prevariscUnitePeriodiciteAnnees', CheckboxType::class, [
                'label' => 'Unité de périodicité en années',
                'required' => false,
            ])
            ->add('prevariscDeactivatePlatau', CheckboxType::class, [
                'label' => "Désactiver Plat'AU",
                'required' => false,
            ])
            ->add('prevariscPlatauNoHealthcheck', CheckboxType::class, [
                'label' => "Désactiver la vérification de la santé de Plat'AU",
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
