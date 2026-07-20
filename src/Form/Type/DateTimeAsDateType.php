<?php

namespace App\Form\Type;

use App\Form\DataTransformer\DateTimeToDateTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class DateTimeAsDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DateTimeToDateTransformer());
    }

    public function getParent(): string
    {
        return DateType::class;
    }
}
