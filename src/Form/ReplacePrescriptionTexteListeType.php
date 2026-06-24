<?php

namespace App\Form;

use App\Entity\PrescriptionTexteListe;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReplacePrescriptionTexteListeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PrescriptionTexteListe $excluded */
        $excluded = $options['excluded'];

        $builder->add('replacementText', EntityType::class, [
            'class' => PrescriptionTexteListe::class,
            'choice_label' => 'libelle',
            'label' => 'Texte de remplacement',
            'query_builder' => function (EntityRepository $er) use ($excluded) {
                return $er->createQueryBuilder('t')
                    ->where('t.id != :excluded')
                    ->setParameter('excluded', $excluded->getId())
                    ->orderBy('t.libelle', 'ASC');
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('excluded');
        $resolver->setAllowedTypes('excluded', PrescriptionTexteListe::class);
    }
}
