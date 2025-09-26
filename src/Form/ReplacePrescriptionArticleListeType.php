<?php

namespace App\Form;

use App\Entity\PrescriptionArticleListe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReplacePrescriptionArticleListeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('replacementArticle', EntityType::class, [
            'class' => PrescriptionArticleListe::class,
            'choice_label' => 'libelle',
            'label' => 'Article de remplacement'
        ]);
    }
}
