<?php

namespace App\Service;

use App\Constant\TypePrescription;
use App\Dto\Prescriptions\PrescriptionsReprises;
use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Entity\PrescriptionDossierAssociation;
use App\Entity\PrescriptionType;
use App\Repository\PrescriptionDossierRepository;
use App\Security\Voter\DossierVoter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class DossierPrescriptionService
{
    /** @var PrescriptionDossierRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    /** @var Security */
    private $security;

    public function __construct(
        PrescriptionDossierRepository $repository,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Security $security
    ) {
        $this->repository = $repository;
        $this->em = $em;
        $this->logger = $logger;
        $this->security = $security;
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
        } else {
            $exploitation = $this->repository->findByDossierAndType($dossier, TypePrescription::EXPLOITATION);
            $amelioration = $this->repository->findByDossierAndType($dossier, TypePrescription::AMELIORATION);

            $ids = array_map(
                function (PrescriptionDossier $p): int { return (int) $p->getId(); },
                array_merge($exploitation, $amelioration)
            );
        }

        $this->repository->renumeroterParIds($dossier, $ids);
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

    /**
     * Reprend les prescriptions de plusieurs dossiers sources vers le dossier cible.
     *
     * Seules les prescriptions non levées (dateLevee nulle) sont copiées.
     * Chaque prescription copiée garde une référence vers son dossier d'origine (dossierReprise).
     * Les séquences de numérotation sont recalculées après l'import.
     *
     * @param Dossier[] $sources
     */
    public function reprendrePrescriptions(Dossier $target, array $sources): PrescriptionsReprises
    {
        $this->em->beginTransaction();
        try {
            $prescriptionsReprises = new PrescriptionsReprises();

            foreach ($sources as $source) {
                if ($source->getEtablissements()->first() !== $target->getEtablissements()->first()) {
                    $this->logger->warning("Les prescriptions du dossier source n'ont pas été reprises car il n'appartient pas au même établissement que le dossier cible.", [
                        'dossier_source' => $source->getId(),
                        'dossier_cible' => $target->getId(),
                    ]);

                    continue;
                }

                if (!$this->security->isGranted(DossierVoter::LECTURE, $source)) {
                    $this->logger->warning("Les prescriptions du dossier source n'ont pas été reprises car l'utilisateur n'a pas les droits nécessaires", [
                        'dossier_source' => $source->getId(),
                    ]);

                    continue;
                }

                foreach (TypePrescription::ALL as $type) {
                    foreach ($this->getPrescriptionsParType($source, $type) as $prescriptionSource) {
                        if (null !== $prescriptionSource->getDateLevee()) {
                            continue;
                        }

                        $copy = new PrescriptionDossier();
                        $copy->setDossier($target);
                        $copy->setType($type);
                        $copy->setDossierReprise($source);
                        $copy->setNumero($this->prochainNumero($target, $type));

                        $prescriptionType = $prescriptionSource->getPrescriptionType();
                        if (null !== $prescriptionType) {
                            $copy->setPrescriptionType($prescriptionType);
                        } else {
                            $copy->setLibelle($prescriptionSource->getLibelle());

                            foreach ($prescriptionSource->getAssociations() as $assocSource) {
                                $assoc = new PrescriptionDossierAssociation();
                                $assoc->setTexte($assocSource->getTexte());
                                $assoc->setArticle($assocSource->getArticle());
                                $copy->addAssociation($assoc);
                            }
                        }

                        $this->renumeroterAssociations($copy);
                        $this->em->persist($copy);

                        $prescriptionsReprises->incrementNombrePrescriptions();
                    }
                }

                $prescriptionsReprises->incrementNombreDossiers();
            }

            $this->em->flush();

            foreach ([TypePrescription::REGLEMENTAIRE, TypePrescription::EXPLOITATION] as $type) {
                $this->renumeroterSequence($target, $type);
            }

            $this->em->commit();

            return $prescriptionsReprises;
        } catch (\Exception $exception) {
            $this->em->rollback();
            throw $exception;
        }
    }
}
