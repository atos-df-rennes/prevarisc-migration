<?php

namespace App\Form;

use App\Entity\Etablissement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DescriptifOriginalType extends AbstractType
{
    public function __construct(private readonly string $risquesNaturelsExemples, private readonly string $risquesTechnologiquesExemples)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('historiqueDescriptif', TextareaType::class, [
                'label' => "Historique de l'établissement",
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('descriptif', TextareaType::class, [
                'label' => 'Descriptif général',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('descriptifDerogation', TextareaType::class, [
                'label' => 'Dérogations accordées',
                'required' => false,
                'attr' => ['rows' => 4],
            ])

            // --- Implantation ---
            ->add('desctechImplantationSurface', IntegerType::class, [
                'label' => 'Surface emprise au sol (m²)',
                'required' => false,
            ])
            ->add('desctechImplantationSurfaceTotale', IntegerType::class, [
                'label' => 'Surface totale (m²)',
                'required' => false,
            ])
            ->add('desctechImplantationSurfaceAccPublic', IntegerType::class, [
                'label' => 'Surface accessible au public (m²)',
                'required' => false,
            ])
            ->add('desctechImplantationShon', IntegerType::class, [
                'label' => 'Surface de plancher (m²)',
                'required' => false,
            ])
            ->add('desctechImplantationNbNiveaux', IntegerType::class, [
                'label' => 'Nombre de niveaux',
                'required' => false,
            ])
            ->add('desctechImplantationPbdn', IntegerType::class, [
                'label' => 'Plancher bas du dernier niveau (m)',
                'required' => false,
            ])

            // --- Desserte ---
            ->add('desctechDesserteNbFacadeLibre', IntegerType::class, [
                'label' => 'Nombre de façades accessibles',
                'required' => false,
            ])
            ->add('desctechDesserteVoieEngin', IntegerType::class, [
                'label' => 'Voie engin',
                'required' => false,
            ])
            ->add('desctechDesserteVoieEchelle', IntegerType::class, [
                'label' => 'Voie échelle',
                'required' => false,
            ])
            ->add('desctechDesserteEspaceLibre', IntegerType::class, [
                'label' => 'Espace libre',
                'required' => false,
            ])

            // --- Isolement ---
            ->add('desctechIsolementLateralCf', IntegerType::class, [
                'label' => 'Latéral CF (minute)',
                'required' => false,
            ])
            ->add('desctechIsolementSuperposeCf', IntegerType::class, [
                'label' => 'Superposé CF (minute)',
                'required' => false,
            ])
            ->add('desctechIsolementVisAVis', IntegerType::class, [
                'label' => 'Vis-à-vis (m)',
                'required' => false,
            ])

            // --- Stabilité au feu ---
            ->add('desctechStabiliteStructureSf', IntegerType::class, [
                'label' => 'Structure SF (minute)',
                'required' => false,
            ])
            ->add('desctechStabilitePlancher', IntegerType::class, [
                'label' => 'Plancher SF (minute)',
                'required' => false,
            ])

            // --- Distribution intérieure ---
            ->add('desctechDistributionCloisonnementTrad', CheckboxType::class, [
                'label' => 'Cloisonnement traditionnel',
                'required' => false,
            ])
            ->add('desctechDistributionSecteurs', CheckboxType::class, [
                'label' => 'Secteurs',
                'required' => false,
            ])
            ->add('desctechDistributionCompartiments', CheckboxType::class, [
                'label' => 'Compartiments',
                'required' => false,
            ])

            // --- Locaux à risques ---
            ->add('desctechLocauxRisqueNbMoyens', IntegerType::class, [
                'label' => 'Nombre de locaux à risques moyens',
                'required' => false,
            ])
            ->add('desctechLocauxRisqueNbImportants', IntegerType::class, [
                'label' => 'Nombre de locaux à risques importants',
                'required' => false,
            ])

            // --- Espaces d'attentes sécurisés ---
            ->add('desctechEspacesNombre', IntegerType::class, [
                'label' => 'Nombre',
                'required' => false,
            ])
            ->add('desctechEspacesNiveauConcerne', IntegerType::class, [
                'label' => 'Nombre de niveaux concernés',
                'required' => false,
            ])

            // --- Désenfumage ---
            ->add('desctechDesenfumageNaturel', CheckboxType::class, [
                'label' => 'Naturel',
                'required' => false,
            ])
            ->add('desctechDesenfumageMecanique', CheckboxType::class, [
                'label' => 'Mécanique',
                'required' => false,
            ])
            ->add('desctechDesenfumageCommentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            // --- Chaufferies ---
            ->add('desctechChaufferiesNb', IntegerType::class, [
                'label' => 'Nombre',
                'required' => false,
            ])
            ->add('desctechChaufferiesPuissMax', IntegerType::class, [
                'label' => 'Puissance max (kw)',
                'required' => false,
            ])
            ->add('desctechChaufferiesPuissanceTotale', IntegerType::class, [
                'label' => 'Puissance totale (kw)',
                'required' => false,
            ])
            ->add('desctechChaufferiesNb30Kw', IntegerType::class, [
                'label' => 'Nombre de chaufferies 30kw',
                'required' => false,
            ])
            ->add('desctechChaufferiesNb70Kw', IntegerType::class, [
                'label' => 'Nombre de chaufferies 70kw',
                'required' => false,
            ])
            ->add('desctechChaufferiesNb2Mw', IntegerType::class, [
                'label' => 'Nombre de chaufferies 2mw',
                'required' => false,
            ])

            // --- Coupures d'énergies ---
            ->add('desctechCoupureNrjGaz', TextareaType::class, [
                'label' => 'Gaz',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('desctechCoupureNrjElec', TextareaType::class, [
                'label' => 'Électricité',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('desctechCoupureNrjPhotovoltaique', TextareaType::class, [
                'label' => 'Photovoltaïque',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('desctechCoupureNrjAutre', TextareaType::class, [
                'label' => 'Autre',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            // --- Ascenseurs ---
            ->add('desctechAscenseursNbTotal', IntegerType::class, [
                'label' => 'Nombre ascenseurs total',
                'required' => false,
            ])
            ->add('desctechAscenseursNbAs4', IntegerType::class, [
                'label' => 'Nombre ascenseurs de type AS4',
                'required' => false,
            ])

            // --- Moyens de secours ---
            ->add('desctechMoyensSecoursColonnesSeches', CheckboxType::class, [
                'label' => 'Colonnes sèches',
                'required' => false,
            ])
            ->add('desctechMoyensSecoursColonnesHumides', CheckboxType::class, [
                'label' => 'Colonnes humides',
                'required' => false,
            ])
            ->add('desctechMoyensSecoursRia', CheckboxType::class, [
                'label' => 'RIA',
                'required' => false,
            ])
            ->add('desctechMoyensSecoursSprinkleur', CheckboxType::class, [
                'label' => 'Sprinkleur',
                'required' => false,
            ])
            ->add('desctechMoyensSecoursBrouillardEau', CheckboxType::class, [
                'label' => "Brouillard d'eau",
                'required' => false,
            ])

            // --- PC Sécurité ---
            ->add('desctechPcSecuPresence', CheckboxType::class, [
                'label' => 'Présence',
                'required' => false,
            ])
            ->add('desctechPcSecuLocalisation', TextareaType::class, [
                'label' => 'Localisation',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            // --- SSI ---
            ->add('desctechSsiPresence', CheckboxType::class, [
                'label' => 'Présence',
                'required' => false,
            ])
            ->add('desctechSsiCategorie', TextType::class, [
                'label' => 'Catégorie SSI',
                'required' => false,
                'attr' => ['maxlength' => 1],
            ])
            ->add('desctechSsiAlarmeType', TextType::class, [
                'label' => 'Alarme type',
                'required' => false,
                'attr' => ['maxlength' => 2],
            ])

            // --- Service de sécurité ---
            ->add('desctechServiceSecuEl18', CheckboxType::class, [
                'label' => 'Personne qualifiée désignée (EL18)',
                'required' => false,
            ])
            ->add('desctechServiceSecuPersonnelsDesignes', CheckboxType::class, [
                'label' => 'Personnels désignés',
                'required' => false,
            ])
            ->add('desctechServiceSecuAgentSecu', IntegerType::class, [
                'label' => 'Agent de sécurité',
                'required' => false,
            ])
            ->add('desctechServiceSecuChefEquipe', IntegerType::class, [
                'label' => "Chef d'équipe sécurité",
                'required' => false,
            ])
            ->add('desctechServiceSecuChefDeService', IntegerType::class, [
                'label' => 'Chef de service sécurité',
                'required' => false,
            ])
            ->add('desctechServiceSecuSp', IntegerType::class, [
                'label' => 'Sapeurs Pompiers',
                'required' => false,
            ])
            ->add('desctechServiceSecuCommentaireSp', TextareaType::class, [
                'label' => 'Commentaire sur les SP',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            // --- Défense incendie ---
            ->add('desctechDefensePtEau', IntegerType::class, [
                'label' => "Nombre de point d'eau",
                'required' => false,
            ])
            ->add('desctechDefenseVolumePtEau', IntegerType::class, [
                'label' => "Volume des points d'eau (m3)",
                'required' => false,
            ])
            ->add('desctechDefensePtEauCommentaire', TextareaType::class, [
                'label' => "Commentaires sur le / les points d'eau naturel",
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('desctechDefensePi', IntegerType::class, [
                'label' => 'PI',
                'required' => false,
            ])
            ->add('desctechDefenseBi', IntegerType::class, [
                'label' => 'BI',
                'required' => false,
            ])
            ->add('desctechDefenseDebitSimultane', IntegerType::class, [
                'label' => 'Débit simultané (m3/h)',
                'required' => false,
            ])

            // --- Risques ---
            ->add('risquesNaturels', TextareaType::class, [
                'label' => 'Risques naturels',
                'required' => false,
                'attr' => ['rows' => 2],
                'help' => '' !== $this->risquesNaturelsExemples
                    ? 'Exemples de risques naturels : '.$this->risquesNaturelsExemples
                    : null,
            ])
            ->add('risquesTechnologiques', TextareaType::class, [
                'label' => 'Risques technologiques',
                'required' => false,
                'attr' => ['rows' => 2],
                'help' => '' !== $this->risquesTechnologiquesExemples
                    ? 'Exemples de risques technologiques : '.$this->risquesTechnologiquesExemples
                    : null,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etablissement::class,
        ]);
    }
}
