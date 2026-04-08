<?php

namespace App\Form\Type\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\TableauRowData;
use App\Entity\Champ;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
            $typeId = null !== $type ? $type->getId() : null;

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

            // Pour les checkboxes, Bootstrap 3 checkbox_widget affiche le label inline
            // à côté de l'input. Dans un tableau, le <th> fait office de label :
            // on masque le <label> visuellement (sr-only) tout en conservant cell.vars.label
            // pour la génération des en-têtes <th> depuis le prototype.
            if (TypeChamp::CHECKBOX === $typeId) {
                $formOptions['label_attr'] = ['class' => 'sr-only'];
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
     * @return string FQCN
     */
    private function resolveLeafType(?int $typeId): string
    {
        switch ($typeId) {
            case TypeChamp::TEXT:
                return ChampTextType::class;
            case TypeChamp::TEXTAREA:
                return ChampTextareaType::class;
            case TypeChamp::SELECT:
                return ChampSelectType::class;
            case TypeChamp::NUMBER:
                return ChampNumberType::class;
            case TypeChamp::CHECKBOX:
                return ChampCheckboxType::class;
            case TypeChamp::DATE:
                return ChampDateType::class;
            default:
                return ChampTextType::class;
        }
    }
}
