<?php

namespace App\Form;

use App\Entity\PrescriptionTexteListe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReplacePrescriptionTexteListeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('texteReplace', EntityType::class, [
            'class' => PrescriptionTexteListe::class,
            'choice_label' => 'libelle',
            'label' => 'Texte de remplacement'
        ]);
    }
}
