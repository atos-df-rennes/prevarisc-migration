<?php

namespace App\Service\Dossier;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Constant\Fonction;
use App\Entity\Dossier;
use App\Entity\DossierDocumentConsulte;
use App\Entity\Etablissement;
use App\Entity\ListeDocumentAjoute;
use App\Repository\DossierRepository;
use App\Service\DossierPrescriptionService;
use App\Service\Prescriptions;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service dédié aux opérations d'initialisation lors de la création d'un dossier.
 *
 * Ces opérations sont spécifiques à la création et n'ont pas lieu lors de l'édition.
 */
class DossierCreationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DossierRepository $dossierRepository, private readonly DossierPrescriptionService $prescriptionService, private readonly Prescriptions $prescriptions)
    {
    }

    /**
     * Copie les contacts pertinents de l'établissement vers le dossier.
     *
     * Copie uniquement les contacts avec des fonctions spécifiques.
     * Non exécuté pour les natures sans contacts (NATURES_SANS_CONTACTS).
     */
    private function copierContactsEtablissement(Dossier $dossier, Etablissement $etablissement): void
    {
        $nature = $dossier->getNature();

        if (null === $nature || in_array($nature->getId(), DossierNature::NATURES_SANS_CONTACTS, true)) {
            return;
        }

        foreach ($etablissement->getContacts() as $contact) {
            $fonction = $contact->getFonction();

            if (null === $fonction) {
                continue;
            }

            if (!in_array($fonction->getId(), Fonction::FONCTIONS_COPIE_CONTACTS, true)) {
                continue;
            }

            $dossier->addContact($contact);
        }
    }

    /**
     * Copie les textes applicables de l'établissement vers le dossier.
     */
    private function copierTextesApplicables(Dossier $dossier, Etablissement $etablissement): void
    {
        $nature = $dossier->getNature();

        if (null === $nature || !in_array($nature->getId(), DossierNature::NATURES_COPIE_TEXTES_APPLICABLES, true)) {
            return;
        }

        foreach ($etablissement->getTextesApplicables() as $texteApplicable) {
            $dossier->addTextesApplicable($texteApplicable);
        }
    }

    /**
     * Crée 5 documents "Attestation de" (ListeDocumentAjoute) pour les natures levée.
     *
     * Uniquement pour les natures 7 (Levée prescriptions) et 19 (Levée avis définitif).
     */
    public function creerDocumentsAttestationLevee(Dossier $dossier): void
    {
        $nature = $dossier->getNature();

        if (null === $nature || !in_array($nature->getId(), DossierNature::LEVEES, true)) {
            return;
        }

        for ($i = 0; $i < 5; ++$i) {
            $document = new ListeDocumentAjoute();
            $document->setLibelle('Attestation de');
            $document->setNature($nature);
            $document->setDossier($dossier);

            $this->entityManager->persist($document);
        }
    }

    /**
     * Copie les documents de la dernière visite périodique de l'établissement vers le nouveau dossier.
     *
     * Copie les DossierDocumentConsulte et les ListeDocumentAjoute.
     */
    private function copierDocumentsDerniereVP(Dossier $dossier, Etablissement $etablissement): void
    {
        $nature = $dossier->getNature();

        if (null === $nature || !in_array($nature->getId(), DossierNature::VISITE_PERIODIQUE, true)) {
            return;
        }

        $derniereVP = $this->dossierRepository->findDerniereVP($etablissement);

        if (null === $derniereVP) {
            return;
        }

        foreach ($derniereVP->getDocumentsConsultes() as $source) {
            $reference = $source->getReference();
            $date = $source->getDate();
            $consulte = $source->isConsulte();
            if (null === $reference) {
                continue;
            }

            if (null === $date) {
                continue;
            }

            if (null === $consulte) {
                continue;
            }

            $copie = new DossierDocumentConsulte();
            $copie->setNature($nature);
            $copie->setReference($reference);
            $copie->setDate($date);
            $copie->setConsulte($consulte);
            $copie->setDocument((string) $source->getDocument());
            $copie->setDossier($dossier);

            $this->entityManager->persist($copie);
        }

        foreach ($derniereVP->getDocumentsAjoutes() as $source) {
            $libelle = $source->getLibelle();

            if (null === $libelle) {
                continue;
            }

            $copie = new ListeDocumentAjoute();
            $copie->setLibelle($libelle);
            $copie->setReference($source->getReference());
            $copie->setDate($source->getDate());
            $copie->setNature($nature);
            $copie->setDossier($dossier);

            $this->entityManager->persist($copie);
        }
    }

    /**
     * Initialise les prescriptions réglementaires par défaut selon le type du dossier.
     *
     * Type 1 (Etude) → prescriptions "etude"
     * Types 2 et 3 (Visite, Groupe de visite) → prescriptions "visite"
     * Aucune action pour les autres types.
     */
    public function initialiserPrescriptionsReglementaires(Dossier $dossier): void
    {
        $type = $dossier->getType();

        if (null === $type) {
            return;
        }

        $typeId = $type->getId();

        if (DossierType::ETUDE === $typeId) {
            $listePrescriptions = $this->prescriptions->getPrescriptions('etude', true);
        } elseif (in_array($typeId, DossierType::TYPES_VISITE, true)) {
            $listePrescriptions = $this->prescriptions->getPrescriptions('visite', true);
        } else {
            return;
        }

        if ([] === $listePrescriptions) {
            return;
        }

        $this->prescriptionService->ajouterPrescriptionsReglementaires($dossier, $listePrescriptions);
    }

    /**
     * Exécute toutes les opérations d'initialisation liées à un établissement.
     *
     * Raccourci pour appeler dans l'ordre : contacts, textes applicables,
     * documents dernière VP. À appeler uniquement si un établissement est associé.
     */
    public function initialiserDepuisEtablissement(Dossier $dossier, Etablissement $etablissement): void
    {
        $this->copierContactsEtablissement($dossier, $etablissement);
        $this->copierTextesApplicables($dossier, $etablissement);
        $this->copierDocumentsDerniereVP($dossier, $etablissement);
    }

    /**
     * Synchronise les textes applicables du dossier vers le premier établissement.
     *
     * Pour les visites et groupes de visites, met à jour les textes applicables
     * du premier établissement rattaché en synchronisant ceux du dossier.
     * Cette opération s'effectue lors de la modification des textes applicables.
     */
    public function synchroniserTextesApplicablesVersEtablissement(Dossier $dossier): void
    {
        $type = $dossier->getType();

        if (null === $type || !in_array($type->getId(), DossierType::TYPES_VISITE, true)) {
            return;
        }

        $etablissements = $dossier->getEtablissements();

        if (0 === $etablissements->count()) {
            return;
        }

        // Récupère le premier établissement
        $premierEtablissement = $etablissements->first();

        if (false === $premierEtablissement) {
            return;
        }

        // Vide et resynchronise la collection des textes applicables de l'établissement
        $premierEtablissement->getTextesApplicables()->clear();

        foreach ($dossier->getTextesApplicables() as $texte) {
            $premierEtablissement->addTextesApplicable($texte);
        }
    }
}
