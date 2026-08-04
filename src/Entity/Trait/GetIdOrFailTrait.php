<?php

namespace App\Entity\Trait;

trait GetIdOrFailTrait
{
    public function getIdOrFail(): int
    {
        if (null === $this->id) {
            throw new \LogicException(sprintf('%s must be persisted before accessing ID', self::class));
        }

        return $this->id;
    }
}
