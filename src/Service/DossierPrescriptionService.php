<?php

namespace App\Service;

use App\Constant\TypePrescription;
use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Entity\PrescriptionDossierAssociation;
use App\Entity\PrescriptionType;
use App\Repository\PrescriptionDossierRepository;
use Doctrine\ORM\EntityManagerInterface;

class DossierPrescriptionService
{
    /** @var PrescriptionDossierRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(PrescriptionDossierRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    /**
     * Retourne les prescriptions d'un dossier pour un type donné.
     *
     * 0 = Rappels réglementaires
     * 1 = Prescriptions liées à l'exploitation
     * 2 = Recommandations liées à l'amélioration
     *
     * @return PrescriptionDossier[]
     */
    public function getPrescriptionsParType(Dossier $dossier, int $type): array
    {
        return $this->repository->findByDossierAndType($dossier, $type);
    }

    /**
     * Réordonne les prescriptions selon les tableaux d'IDs reçus du drag & drop.
     *
     * - $idsRegl       : IDs des rappels réglementaires (type 0) dans leur nouvel ordre
     * - $idsAutres     : IDs des exploitations + améliorations (types 1+2) dans leur nouvel ordre,
     *                    numérotés en séquence continue (reproduit le comportement legacy)
     *
     * @param int[] $idsRegl
     * @param int[] $idsAutres
     */
    public function reordonner(Dossier $dossier, array $idsRegl, array $idsAutres): void
    {
        $this->repository->renumeroterParIds($dossier, $idsRegl);
        $this->repository->renumeroterParIds($dossier, $idsAutres);
    }

    /**
     * Sauvegarde une prescription (création ou modification).
     *
     * Lors d'une création, attribue le prochain numéro selon le type.
     * Lors d'une modification changeant de type, déplace la prescription et
     * renumérote les séquences impactées.
     *
     * Types 1 (exploitation) et 2 (amélioration) partagent une numérotation continue.
     *
     * $ancienType doit être capturé avant handleRequest() dans le contrôleur afin de
     * détecter un éventuel changement de type (la prescription porte déjà le nouveau type
     * au moment de l'appel).
     */
    public function sauvegarder(Dossier $dossier, PrescriptionDossier $prescription, ?int $ancienType = null): void
    {
        $isNew = null === $prescription->getId();
        $nouveauType = $prescription->getType();

        if (null === $nouveauType) {
            throw new \InvalidArgumentException('Le type de la prescription est obligatoire.');
        }

        if ($isNew) {
            $prescription->setDossier($dossier);
            $prescription->setNumero($this->prochainNumero($dossier, $nouveauType));
        } elseif ($ancienType !== $nouveauType) {
            $prescription->setNumero($this->prochainNumero($dossier, $nouveauType));
        }

        $this->renumeroterAssociations($prescription);

        $this->em->persist($prescription);
        $this->em->flush();

        // Renumérote les séquences concernées après persistance
        $this->renumeroterSequence($dossier, $nouveauType);
        if (null !== $ancienType && $ancienType !== $nouveauType) {
            $this->renumeroterSequence($dossier, $ancienType);
        }
    }

    /**
     * Supprime une prescription et renumérote la séquence du type concerné.
     */
    public function supprimer(PrescriptionDossier $prescription): void
    {
        $dossier = $prescription->getDossier();
        $type = $prescription->getType();

        $this->em->remove($prescription);
        $this->em->flush();

        if (null !== $dossier && null !== $type) {
            $this->renumeroterSequence($dossier, $type);
        }
    }

    /**
     * Prépare une prescription pour l'édition en la convertissant au format "libre".
     *
     * Si la prescription est liée à un type (prescriptionType non null), cette méthode
     * copie le libellé et les associations du type dans les champs propres à la prescription,
     * puis détache le lien vers le type. Ainsi le formulaire travaille toujours sur
     * les propriétés écrivables libelle/associations, quelle que soit l'origine.
     *
     * Cette opération est en mémoire uniquement : aucune persistance n'est effectuée.
     */
    public function preparerPourEdition(PrescriptionDossier $prescription): void
    {
        $prescriptionType = $prescription->getPrescriptionType();

        if (null === $prescriptionType) {
            return;
        }

        $prescription->setLibelle($prescriptionType->getLibelle());

        foreach ($prescriptionType->getAssociations() as $typeAssoc) {
            $assoc = new PrescriptionDossierAssociation();
            $assoc->setTexte($typeAssoc->getTexte());
            $assoc->setArticle($typeAssoc->getArticle());
            $prescription->addAssociation($assoc);
        }

        $prescription->setPrescriptionType(null);
    }

    /**
     * Ajoute une prescription type à un dossier.
     *
     * Crée une PrescriptionDossier liée au PrescriptionType (sans copier le
     * libellé — effectiveLibelle le lit dynamiquement depuis le type).
     * Attribue le prochain numéro selon les règles de séquence du type.
     *
     * @throws \Exception
     */
    public function ajouterDepuisType(
        Dossier $dossier,
        PrescriptionType $prescriptionType,
        int $typePrescription
    ): PrescriptionDossier {
        $this->em->beginTransaction();
        try {
            $prescription = new PrescriptionDossier();
            $prescription->setDossier($dossier);
            $prescription->setType($typePrescription);
            $prescription->setPrescriptionType($prescriptionType);
            $prescription->setNumero($this->prochainNumero($dossier, $typePrescription));

            $this->em->persist($prescription);
            $this->em->flush();

            $this->renumeroterSequence($dossier, $typePrescription);

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();
            throw $exception;
        }

        return $prescription;
    }

    /**
     * Calcule le prochain numéro disponible pour un type donné.
     * Les types 1 et 2 partagent une séquence continue.
     */
    private function prochainNumero(Dossier $dossier, int $type): int
    {
        $types = TypePrescription::REGLEMENTAIRE === $type
            ? [TypePrescription::REGLEMENTAIRE]
            : [TypePrescription::EXPLOITATION, TypePrescription::AMELIORATION];

        return $this->repository->findMaxNumeroByDossierAndTypes($dossier, $types) + 1;
    }

    /**
     * Renumérote toutes les prescriptions d'une séquence en ordre continu à partir de 1.
     * Les types 1 et 2 forment une séquence commune.
     */
    private function renumeroterSequence(Dossier $dossier, int $type): void
    {
        if (TypePrescription::REGLEMENTAIRE === $type) {
            $ids = array_map(
                function (PrescriptionDossier $p): int { return (int) $p->getId(); },
                $this->repository->findByDossierAndType($dossier, TypePrescription::REGLEMENTAIRE)
            );
            $this->repository->renumeroterParIds($dossier, $ids);
        } else {
            $exploitation = $this->repository->findByDossierAndType($dossier, TypePrescription::EXPLOITATION);
            $amelioration = $this->repository->findByDossierAndType($dossier, TypePrescription::AMELIORATION);

            $ids = array_map(
                function (PrescriptionDossier $p): int { return (int) $p->getId(); },
                array_merge($exploitation, $amelioration)
            );
            $this->repository->renumeroterParIds($dossier, $ids);
        }
    }

    /**
     * Renumérote les associations d'une prescription de façon séquentielle (1, 2, 3…).
     * Nécessaire car NUM_PRESCRIPTION_DOSSIERASSOC fait partie de la clé primaire composite.
     */
    private function renumeroterAssociations(PrescriptionDossier $prescription): void
    {
        $numero = 1;
        foreach ($prescription->getAssociations() as $association) {
            $association->setNumero($numero);
            ++$numero;
        }
    }
}
