<?php

namespace App\Form;

use App\Entity\Champ;
use App\Entity\TypeChamp;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Champ $champ */
            $champ = $event->getData();
            $form = $event->getForm();

            $typeChamp = $champ->getType();
            if (null === $typeChamp || 'Parent' !== $typeChamp->getType()) {
                $tableau = null;
            } else {
                $tableau = $champ->getTableau();
            }

            $form->add('tableau', CheckboxType::class, [
                'label' => 'Afficher comme tableau',
                'required' => false,
                'data' => $tableau,
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
