<?php

namespace App\Form;

use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
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
                'choice_attr' => function (Privileges $privileges): ?array {
                    $resource = $privileges->getResource();

                    if (!$resource instanceof Resources) {
                        return null;
                    }

                    return ['data-resource' => $resource->getText().' ('.$resource->getName().')'];
                },
                'choice_label' => fn (Privileges $privilege): string => $privilege->getText().' ('.$privilege->getName().')',
                'expanded' => true,
                'multiple' => true,
                'group_by' => function (Privileges $privilege): ?string {
                    $resource = $privilege->getResource();

                    if (!$resource instanceof Resources) {
                        return null;
                    }

                    return $resource->getText().' ('.$resource->getName().')';
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
