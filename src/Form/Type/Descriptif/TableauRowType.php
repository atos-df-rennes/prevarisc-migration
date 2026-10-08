<?php

namespace App\Form\Type\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Représente une ligne d'un champ tableau (PARENT avec tableau=true).
 *
 * Chaque colonne correspond à un Champ enfant.
 * Le property_path 'cells[{champId}]' assure le binding vers TableauRowData::$cells.
 */
class TableauRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Champ[] $childChamps */
        $childChamps = $options['child_champs'];

        foreach ($childChamps as $childChamp) {
            $champId = $childChamp->getId();
            $type = $childChamp->getType();
            $typeId = $type?->getId();

            $formType = $this->resolveLeafType($typeId);
            $formOptions = [
                'label' => $childChamp->getNom(),
                'required' => false,
                'property_path' => 'cells['.$champId.']',
            ];

            if (TypeChamp::SELECT === $typeId) {
                $choices = [];
                foreach ($childChamp->getValeursListe() as $vl) {
                    $choices[$vl->getValeur()] = $vl->getValeur();
                }

                $formOptions['choices'] = $choices;
            }

            $builder->add((string) $champId, $formType, $formOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TableauRowData::class,
        ]);
        $resolver->setRequired('child_champs');
        $resolver->setAllowedTypes('child_champs', 'array');
    }

    /**
     * Retourne le FQCN du FormType feuille correspondant au TypeChamp.
     *
     * @return class-string<FormTypeInterface> FQCN
     */
    private function resolveLeafType(?int $typeId): string
    {
        return match ($typeId) {
            TypeChamp::TEXTAREA => ChampTextareaType::class,
            TypeChamp::SELECT => ChampSelectType::class,
            TypeChamp::NUMBER => ChampNumberType::class,
            TypeChamp::CHECKBOX => ChampCheckboxType::class,
            TypeChamp::DATE => ChampDateType::class,
            default => ChampTextType::class,
        };
    }
}
