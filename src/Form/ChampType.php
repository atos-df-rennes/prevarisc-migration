<?php

namespace App\Form;

use App\Entity\Champ;
use App\Entity\TypeChamp;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('type', EntityType::class, [
                'class' => TypeChamp::class,
                'choice_label' => 'type',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var Champ $champ */
            $champ = $event->getData();
            $form = $event->getForm();

            $typeChamp = $champ->getType();
            $tableau = null === $typeChamp || 'Parent' !== $typeChamp->getType() ? null : $champ->getTableau();

            $form->add('tableau', CheckboxType::class, [
                'label' => 'Afficher comme tableau',
                'required' => false,
                'data' => $tableau,
            ]);
        });

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var Champ $champ */
            $champ = $event->getData();
            $form = $event->getForm();

            $typeChamp = $champ->getType();
            $valeursListe = null === $typeChamp || 'Liste' !== $typeChamp->getType() ? [] : $champ->getValeursListe();

            $form->add('valeursListe', CollectionType::class, [
                'entry_type' => ChampValeursListeType::class,
                'required' => false,
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
        ]);
    }
}
