<?php

namespace App\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class DossierAjouterEtablissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('idEtablissement', HiddenType::class, [
            'constraints' => [new Assert\NotBlank(), new Assert\Type('numeric')],
        ]);
    }
}
