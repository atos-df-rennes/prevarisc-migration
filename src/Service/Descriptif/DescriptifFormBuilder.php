<?php

namespace App\Service\Descriptif;

use App\Dto\Descriptif\DescriptifFormData;
use App\Entity\Rubrique;
use App\Form\Type\Descriptif\ChampValueType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Construit le FormInterface Symfony pour les formulaires personnalisés.
 *
 * La structure (rubriques, champs) est dynamique (stockée en BDD) → construction programmatique.
 */
class DescriptifFormBuilder
{
    /**
     * Construit le formulaire Symfony depuis un DescriptifFormData et la structure de rubriques.
     *
     * @param list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}> $rubriques
     */
    public function buildForm(
        FormFactoryInterface $formFactory,
        DescriptifFormData $formData,
        array $rubriques
    ): FormInterface {
        $builder = $formFactory->createNamedBuilder(
            'descriptif',
            FormType::class,
            $formData,
            ['csrf_protection' => true]
        );

        foreach ($rubriques as $item) {
            /** @var Rubrique $rubrique */
            $rubrique = $item['rubrique'];
            $rubriqueId = (int) $rubrique->getId();
            $rubriqueData = $formData->getRubrique($rubriqueId);

            $rubriqueBuilder = $formFactory->createNamedBuilder(
                'rubrique_'.$rubriqueId,
                FormType::class,
                $rubriqueData,
                ['auto_initialize' => false, 'label' => $rubrique->getNom()]
            );

            $rubriqueBuilder->add('display', CheckboxType::class, [
                'required' => false,
                'label' => $rubrique->getNom(),
            ]);

            foreach ($item['champs'] as $champData) {
                /** @var \App\Entity\Champ $champ */
                $champ = $champData['champ'];
                $champId = (int) $champ->getId();

                $rubriqueBuilder->add(
                    'champ_'.$champId,
                    ChampValueType::class,
                    [
                        'label' => $champ->getNom(),
                        'data' => null !== $rubriqueData
                            ? $rubriqueData->getChamp($champId)
                            : null,
                    ]
                );
            }

            $builder->add($rubriqueBuilder);
        }

        return $builder->getForm();
    }
}
