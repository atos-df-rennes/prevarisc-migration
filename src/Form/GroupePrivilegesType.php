<?php

namespace App\Form;

use App\Entity\Groupe;
use App\Entity\Privileges;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupePrivilegesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('privileges', EntityType::class, [
                'class' => Privileges::class,
                'label' => 'Privilèges',
                'choice_attr' => function (Privileges $privileges) {
                    $resource = $privileges->getResource();

                    if (null === $resource) {
                        return null;
                    }

                    return ['data-resource' => $resource->getText() . ' (' . $resource->getName() . ')'];
                },
                'choice_label' => function (Privileges $privilege) {
                    return $privilege->getText() . ' (' . $privilege->getName() . ')';
                },
                'expanded' => true,
                'multiple' => true,
                'group_by' => function (Privileges $privilege) {
                    $resource = $privilege->getResource();

                    if (null === $resource) {
                        return null;
                    }

                    return $resource->getText() . ' (' . $resource->getName() . ')';
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
        ]);
    }
}
