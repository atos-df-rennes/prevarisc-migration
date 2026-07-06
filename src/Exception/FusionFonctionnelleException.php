<?php

namespace App\Exception;

/**
 * Erreur fonctionnelle limitée à une opération de fusion (un « lot ») : commune
 * introuvable, libellé incohérent, informations manquantes, etc. Elle n'interrompt
 * pas le traitement global : le lot concerné est ignoré et signalé en avertissement,
 * les lots suivants continuent d'être traités.
 */
final class FusionFonctionnelleException extends \RuntimeException
{
}
