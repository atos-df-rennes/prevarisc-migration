<?php

namespace App\Controller\Contract;

/**
 * Marque un controller comme gérant les prescriptions d'un dossier.
 *
 * Les routes de ces controllers ne doivent pas être accessibles pour les
 * dossiers dont la nature ou le type ne permet pas les prescriptions
 * (DossierNature::NATURES_SANS_PRESCRIPTIONS, DossierType::TYPES_SANS_PRESCRIPTIONS).
 * La vérification est effectuée par PrescriptionsAccessSubscriber.
 */
interface DossierPrescriptionsAccessInterface
{
}
