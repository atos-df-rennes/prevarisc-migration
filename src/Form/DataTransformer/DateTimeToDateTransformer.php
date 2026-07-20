<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimeToDateTransformer implements DataTransformerInterface
{
    /**
     * Transform DateTime to Date string (Y-m-d format).
     * This is used when displaying the form.
     *
     * @param ?\DateTimeInterface $value
     */
    public function transform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException(sprintf('Expected a DateTimeInterface, but got %s', get_debug_type($value)));
        }

        return $value->format('Y-m-d');
    }

    /**
     * Transform Date string back to DateTime at midnight.
     * This is used when the form is submitted.
     *
     * @param ?string $value
     */
    public function reverseTransform($value): ?\DateTimeInterface
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $value);
            if (false === $dateTime) {
                throw new \Exception('Invalid date format');
            }

            // Set time to 00:00:00 to preserve datetime type in DB
            $dateTime->setTime(0, 0, 0);

            return $dateTime;
        } catch (\Exception $exception) {
            throw new TransformationFailedException(sprintf('Failed to transform date "%s": %s', $value, $exception->getMessage()), $exception->getCode(), $exception);
        }
    }
}
