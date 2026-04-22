<?php

namespace App\Form\Dossier;

use App\Entity\DossierDocumentUrbanisme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DossierDocumentUrbanismeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: PC 123 456',
                ],
                'row_attr' => [
                    'class' => 'col-sm-10',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 100]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierDocumentUrbanisme::class,
        ]);
    }
}
