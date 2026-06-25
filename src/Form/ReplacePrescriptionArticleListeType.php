<?php

namespace App\Form;

use App\Entity\PrescriptionArticleListe;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReplacePrescriptionArticleListeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PrescriptionArticleListe $excluded */
        $excluded = $options['excluded'];

        $builder->add('replacementArticle', EntityType::class, [
            'class' => PrescriptionArticleListe::class,
            'choice_label' => 'libelle',
            'label' => 'Article de remplacement',
            'query_builder' => function (EntityRepository $er) use ($excluded) {
                return $er->createQueryBuilder('a')
                    ->where('a.id != :excluded')
                    ->setParameter('excluded', $excluded->getId())
                    ->orderBy('a.libelle', 'ASC');
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('excluded');
        $resolver->setAllowedTypes('excluded', PrescriptionArticleListe::class);
    }
}
