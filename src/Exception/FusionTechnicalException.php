<?php

namespace App\Exception;

/**
 * Erreur technique bloquante survenue avant ou pendant la fusion des communes
 * (fichier illisible, JSON invalide, etc.). Elle interrompt l'intégralité du
 * traitement : aucune fusion n'est réalisée.
 */
final class FusionTechnicalException extends \RuntimeException
{
}
