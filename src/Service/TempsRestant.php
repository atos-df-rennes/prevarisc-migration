<?php

namespace App\Service;

use DateTime;

final class TempsRestant{
    public const SEUIL_OK = 6;
    public const SEUIL_WARN = 3;

    public function calculate(?DateTime $limitDate): ?string
    {
        if (null === $limitDate) {
            return null;
        }
        $now = (new DateTime())->setTime(0, 0, 0);

        $diff = $now->diff($limitDate);

        $readableDiff = '';

        if ($diff->y > 0) {
            $ans = $diff->y > 1 ? 'ans' : 'an';
            $readableDiff .= sprintf('%d %s', $diff->y, $ans);
        }

        if ($diff->m > 0) {
            if ('' !== $readableDiff) {
                $readableDiff .= ' et ';
            }

            $readableDiff .= $diff->m . ' mois';
        }

        if ($diff->d > 0) {
            if ('' !== $readableDiff) {
                $readableDiff .= ' et ';
            }

            $jours = $diff->d > 1 ? 'jours' : 'jour';
            $readableDiff .= sprintf('%d %s', $diff->d, $jours);
        }

        if (1 === $diff->invert) {
            return '- ' . $readableDiff;
        }

        return $readableDiff;
    }

    public function getCouleurTempsRestant(?DateTime $limitDate): string
    {
        $now = (new DateTime())->setTime(0, 0, 0);

        $diff = $now->diff($limitDate);

        if (1 === $diff->invert) {
            return 'label label-inverse';
        }

        if ($diff->y > 0 || $diff->m > self::SEUIL_OK) {
            return 'label label-success';
        }

        if ($diff->m > self::SEUIL_WARN) {
            return 'label label-warning';
        }

        return 'label label-important';
    }
}

