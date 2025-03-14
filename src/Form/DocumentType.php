<?php

namespace App\Form;

use App\Dto\FormDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commission', ChoiceType::class, [
                'choices' => $builder->getData(),
                'choice_label' => function (FormDocument $commission) {
                    return $commission->nom();
                },
                'choice_value' => function (?FormDocument $commission) {
                    return $commission ? $commission->id() : '';
                },
                'group_by' => function (FormDocument $commission) {
                    return $commission->type();
                }
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier à envoyer',
            ])
            ->add('close', ButtonType::class, [
                'label' => 'Fermer',
                'attr' => ['class' => 'btn btn-default'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'btn btn-success'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
