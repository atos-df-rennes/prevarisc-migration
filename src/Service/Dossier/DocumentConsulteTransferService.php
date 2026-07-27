<?php

namespace App\Service\Dossier;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType as DossierTypeEntity;
use App\Repository\DossierDocumentConsulteRepository;
use App\Repository\ListeDocumentAjouteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion du transfert/suppression des documents consultés
 * lors du changement de type ou nature d'un dossier.
 *
 * @see prevarisc/application/services/Dossier.php::transfertOuSupprimeDocumentsConsultes()
 */
final class DocumentConsulteTransferService
{
    public function __construct(private EntityManagerInterface $entityManager, private DossierDocumentConsulteRepository $documentConsulteRepository, private ListeDocumentAjouteRepository $documentAjouteRepository)
    {
    }

    /**
     * Transfère ou supprime les documents consultés lors d'un changement de type/nature.
     *
     * Règles de compatibilité :
     * - Types visites (2, 3) sont interchangeables entre eux
     * - Natures RT/VAO (20, 47, 25, 48) sont interchangeables entre elles
     * - Si incompatible : suppression des documents consultés/ajoutés
     * - Si compatible : mise à jour de la nature
     *
     * @param Dossier                 $dossier        Dossier en cours d'édition
     * @param DossierTypeEntity       $nouveauType    Nouveau type sélectionné
     * @param DossierNatureListe|null $nouvelleNature Nouvelle nature sélectionnée
     */
    public function transfertOuSupprimeDocumentsConsultes(
        Dossier $dossier,
        DossierTypeEntity $nouveauType,
        ?DossierNatureListe $nouvelleNature
    ): void {
        $ancienType = $dossier->getType();
        if (null === $ancienType) {
            return; // Pas de type : rien à faire
        }

        $ancienTypeId = $ancienType->getId();
        $nouveauTypeId = $nouveauType->getId();

        if (null === $ancienTypeId || null === $nouveauTypeId) {
            return; // ID type null : situation anormale
        }

        $ancienneNature = $dossier->getNature();
        $ancienneNatureId = $ancienneNature instanceof DossierNatureListe ? $ancienneNature->getId() : null;
        $nouvelleNatureId = $nouvelleNature instanceof DossierNatureListe ? $nouvelleNature->getId() : null;

        // Pas de changement : rien à faire
        if ($ancienTypeId === $nouveauTypeId && $ancienneNatureId === $nouvelleNatureId) {
            return;
        }

        // Vérification compatibilité type
        $hasIncompatibleTypes = $this->hasIncompatibleTypes($ancienTypeId, $nouveauTypeId);

        // Vérification compatibilité nature
        $hasIncompatibleNatures = $this->hasIncompatibleNatures($ancienneNatureId, $nouvelleNatureId);

        // Si incompatible : suppression des documents consultés/ajoutés
        if ($hasIncompatibleTypes || $hasIncompatibleNatures) {
            $this->supprimerDocumentsConsultes($dossier);

            return;
        }

        // Si compatible : mise à jour de la nature
        $this->updateNatureDocumentsConsultes($dossier, $nouvelleNature);
    }

    /**
     * Vérifie si les types sont incompatibles.
     *
     * Les types sont compatibles uniquement si :
     * - Ils sont identiques
     * - Les deux sont des types visite (2 ou 3)
     */
    private function hasIncompatibleTypes(int $ancienTypeId, int $nouveauTypeId): bool
    {
        $ancienEstVisite = in_array($ancienTypeId, DossierType::TYPES_VISITE, true);
        $nouveauEstVisite = in_array($nouveauTypeId, DossierType::TYPES_VISITE, true);

        // Incompatible si :
        // - Ancien = visite ET nouveau != visite
        // - Ancien != visite ET nouveau != visite ET différents
        return ($ancienEstVisite && !$nouveauEstVisite)
            || (!$ancienEstVisite && $ancienTypeId !== $nouveauTypeId);
    }

    /**
     * Vérifie si les natures sont incompatibles.
     *
     * Les natures sont compatibles uniquement si :
     * - Elles sont identiques
     * - Les deux sont RT ou VAO (20, 47, 25, 48)
     */
    private function hasIncompatibleNatures(?int $ancienneNatureId, ?int $nouvelleNatureId): bool
    {
        if (null === $ancienneNatureId || null === $nouvelleNatureId) {
            return false; // Pas de nature (une des deux) : compatible
        }

        // Vérifie si une seule des deux natures est dans RT/VAO
        $natures = [$ancienneNatureId, $nouvelleNatureId];
        $naturesHorsRtVao = array_diff($natures, DossierNature::NATURES_RT_VAO);

        // Incompatible si une seule nature est hors RT/VAO (count = 1)
        return 1 === count($naturesHorsRtVao);
    }

    /**
     * Supprime tous les documents consultés et ajoutés du dossier.
     */
    private function supprimerDocumentsConsultes(Dossier $dossier): void
    {
        // Suppression documents consultés
        $documentsConsultes = $this->documentConsulteRepository->findBy(['dossier' => $dossier]);
        foreach ($documentsConsultes as $doc) {
            $this->entityManager->remove($doc);
        }

        // Suppression documents ajoutés
        $documentsAjoutes = $this->documentAjouteRepository->findBy(['dossier' => $dossier]);
        foreach ($documentsAjoutes as $doc) {
            $this->entityManager->remove($doc);
        }
    }

    /**
     * Met à jour la nature des documents consultés et ajoutés.
     */
    private function updateNatureDocumentsConsultes(Dossier $dossier, ?DossierNatureListe $nouvelleNature): void
    {
        // Si la nouvelle nature est null, on ne peut pas mettre à jour (setNature n'accepte pas null)
        // Dans ce cas, on ne fait rien (les documents gardent leur ancienne nature)
        if (null === $nouvelleNature) {
            return;
        }

        // Mise à jour nature dans documents consultés
        $documentsConsultes = $this->documentConsulteRepository->findBy(['dossier' => $dossier]);
        foreach ($documentsConsultes as $doc) {
            $doc->setNature($nouvelleNature);
        }

        // Mise à jour nature dans documents ajoutés
        $documentsAjoutes = $this->documentAjouteRepository->findBy(['dossier' => $dossier]);
        foreach ($documentsAjoutes as $doc) {
            $doc->setNature($nouvelleNature);
        }
    }
}
