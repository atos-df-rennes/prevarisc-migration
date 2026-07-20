<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateToDateTimeTransformer implements DataTransformerInterface
{
    /**
     * Transform DateTime to DateTime (pass through).
     * DateType displays it as a date already.
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * Ensure a proper DateTime object with time set to 00:00:00.
     *
     * @return ?\DateTimeInterface
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            // If immutable, convert to mutable
            $mutable = new \DateTime(
                $value->format('Y-m-d H:i:s.u'),
                $value->getTimezone()
            );
            $mutable->setTime(0, 0, 0);

            return $mutable;
        }

        if ($value instanceof \DateTime) {
            // If mutable, just set the time
            $value->setTime(0, 0, 0);

            return $value;
        }

        throw new TransformationFailedException(
            sprintf('Expected a DateTimeInterface, but got %s', get_debug_type($value))
        );
    }
}
