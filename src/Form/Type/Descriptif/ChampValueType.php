<?php

namespace App\Form\Type\Descriptif;

use App\Constant\TypeChamp;
use App\Dto\Descriptif\ChampValueData;
use App\Repository\ChampRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dispatcheur dynamique : ajoute le bon sous-type selon le TypeChamp de la valeur.
 *
 * Utilise un listener PRE_SET_DATA car le type est stocké en BDD et inconnu
 * au moment de la construction du FormType.
 */
class ChampValueType extends AbstractType
{
    /** @var ChampRepository */
    private $champRepository;

    public function __construct(ChampRepository $champRepository)
    {
        $this->champRepository = $champRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $champRepository = $this->champRepository;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($champRepository): void {
                $data = $event->getData();
                $form = $event->getForm();

                if (!$data instanceof ChampValueData) {
                    return;
                }

                $champ = $champRepository->find($data->getChampId());
                if (null === $champ) {
                    return;
                }

                $type = $champ->getType();
                $typeId = null !== $type ? $type->getId() : null;

                switch ($typeId) {
                    case TypeChamp::TEXT:
                        $form->add('value', ChampTextType::class, ['label' => false]);
                        break;

                    case TypeChamp::TEXTAREA:
                        $form->add('value', ChampTextareaType::class, ['label' => false]);
                        break;

                    case TypeChamp::SELECT:
                        $choices = [];
                        foreach ($champ->getValeursListe() as $vl) {
                            $choices[$vl->getValeur()] = $vl->getValeur();
                        }

                        $form->add('value', ChampSelectType::class, [
                            'label' => false,
                            'choices' => $choices,
                        ]);
                        break;

                    case TypeChamp::NUMBER:
                        $form->add('value', ChampNumberType::class, ['label' => false]);
                        break;

                    case TypeChamp::CHECKBOX:
                        $form->add('value', ChampCheckboxType::class, ['label' => false]);
                        break;

                    case TypeChamp::DATE:
                        $form->add('value', ChampDateType::class, ['label' => false]);
                        break;

                    case TypeChamp::PARENT:
                        if ($champ->getTableau()) {
                            $form->add('lignes', CollectionType::class, [
                                'entry_type' => TableauRowType::class,
                                'entry_options' => [
                                    'child_champs' => $champ->getEnfants()->toArray(),
                                ],
                                'allow_add' => true,
                                'allow_delete' => true,
                                'by_reference' => false,
                                'label' => false,
                                'prototype' => true,
                            ]);
                        } else {
                            $form->add('sousChamps', CollectionType::class, [
                                'entry_type' => self::class,
                                'allow_add' => false,
                                'allow_delete' => false,
                                'by_reference' => false,
                                'label' => false,
                            ]);
                        }

                        break;
                }
            }
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Définit le label depuis le nom du champ BDD uniquement quand il n'est pas
        // explicitement fourni (cas des sous-champs de PARENT non-tableau dans une CollectionType).
        if (null !== $options['label']) {
            return;
        }

        $data = $form->getData();
        if (!$data instanceof ChampValueData) {
            return;
        }

        $champ = $this->champRepository->find($data->getChampId());
        if (null !== $champ) {
            /** @var array<string, mixed> $vars */
            $vars = $view->vars;
            $vars['label'] = $champ->getNom();
            $view->vars = $vars;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChampValueData::class,
        ]);
    }
}
