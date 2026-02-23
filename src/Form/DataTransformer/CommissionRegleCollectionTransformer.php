<?php

namespace App\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforme une Collection d'entités (localSommeil ou etudeVisite) en tableau d'entiers
 * et inversement, pour permettre un rendu via ChoiceType standard (checkboxes).
 *
 * Les entités de liaison (CommissionRegleLocalSommeil, CommissionRegleEtudeVisite)
 * stockent la valeur entière comme identifiant dans leur table de jointure.
 */
class CommissionRegleCollectionTransformer implements DataTransformerInterface
{
    /** @var string Classe de l'entité de liaison (ex: CommissionRegleLocalSommeil) */
    private $entityClass;

    /** @var string Nom du getter de la valeur entière (ex: 'getLocalSommeil') */
    private $getter;

    /** @var string Nom du setter de la valeur entière (ex: 'setLocalSommeil') */
    private $setter;

    public function __construct(string $entityClass, string $getter, string $setter)
    {
        $this->entityClass = $entityClass;
        $this->getter = $getter;
        $this->setter = $setter;
    }

    /**
     * Collection<Entity> → array<int> (pour le rendu des checkboxes).
     *
     * @return array<int>
     */
    public function transform($collection): array
    {
        if (null === $collection) {
            return [];
        }

        if (!$collection instanceof Collection) {
            throw new TransformationFailedException('Expected a Doctrine Collection.');
        }

        $values = [];
        foreach ($collection as $entity) {
            $raw = $entity->{$this->getter}();
            if (!is_int($raw) && !is_string($raw) && null !== $raw) {
                throw new TransformationFailedException('Entity getter must return int, string or null.');
            }

            $values[] = (int) $raw;
        }

        return $values;
    }

    /**
     * array<int> → Collection<Entity> (après soumission du formulaire).
     */
    public function reverseTransform($values): Collection
    {
        $collection = new ArrayCollection();

        if (null === $values || [] === $values) {
            return $collection;
        }

        if (!is_array($values)) {
            throw new TransformationFailedException('Expected an array.');
        }

        foreach ($values as $value) {
            $entity = new $this->entityClass();
            if (!is_int($value) && !is_string($value)) {
                throw new TransformationFailedException('Each value must be an integer or string.');
            }

            $entity->{$this->setter}((int) $value);
            $collection->add($entity);
        }

        return $collection;
    }
}
