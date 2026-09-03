<?php

namespace App\Form;

use App\Entity\Champ;
use App\Entity\TypeChamp;
use App\Repository\TypeChampRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampType extends AbstractType
{
    public function __construct(private readonly TypeChampRepository $typeChampRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['types'] ??= $this->typeChampRepository->findAll();

        $builder
            ->add('nom')
            ->add('type', EntityType::class, [
                'class' => TypeChamp::class,
                'choices' => $options['types'],
                'choice_label' => 'type',
                'attr' => [
                    'disabled' => (bool) $options['is_edit'],
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            /** @var Champ $champ */
            $champ = $event->getData();
            $form = $event->getForm();

            $typeChamp = $champ->getType();
            $tableau = null === $typeChamp || 'Parent' !== $typeChamp->getType() ? null : $champ->getTableau();

            $form->add('tableau', CheckboxType::class, [
                'label' => 'Afficher comme tableau',
                'required' => false,
                'data' => $tableau,
                'attr' => [
                    'disabled' => (bool) $options['is_edit'],
                ],
            ]);
        });

        /*
         * Les champs type et tableau sont disabled et les valeurs ne sont donc pas soumises au formulaire.
         * Cela a pour impact de réinitialiser les valeurs des champs lors de la sauvegarde. (exemple: tableau coché devient décoché)
         * On récupère donc les valeurs initiales pour les ajouter à la soumission du formulaire.
         */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();

            if (!\is_array($data)) {
                return;
            }

            $form = $event->getForm();
            /** @var Champ $champ */
            $champ = $form->getData();

            $data['tableau'] = $champ->getTableau();

            $type = $champ->getType();
            if (null === $type) {
                return;
            }

            $data['type'] = $type->getId();
            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var Champ $champ */
            $champ = $event->getData();
            $form = $event->getForm();

            $typeChamp = $champ->getType();
            $valeursListe = null === $typeChamp || 'Liste' !== $typeChamp->getType() ? [] : $champ->getValeursListe();

            $form->add('valeursListe', CollectionType::class, [
                'entry_type' => ChampValeursListeType::class,
                'required' => true,
                'data' => $valeursListe,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Champ::class,
            'is_edit' => false, // Par défaut, on considère que c'est une création
            'types' => null,
        ]);
    }
}
