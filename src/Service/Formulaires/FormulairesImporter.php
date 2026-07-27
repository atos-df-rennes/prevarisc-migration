<?php

namespace App\Service\Formulaires;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\ChampValeurListe;
use App\Entity\Rubrique;
use App\Entity\TypeChamp;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\TypeChampRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class FormulairesImporter
{
    public function __construct(private CapsuleRubriqueRepository $capsuleRubriqueRepository, private TypeChampRepository $typeChampRepository, private LoggerInterface $logger)
    {
    }

    /**
     * Supprime complètement une configuration existante de formulaires personnalisés.
     * Seules les capsules rubriques statiques sont conservées.
     */
    public function deleteExistingConfiguration(EntityManagerInterface $entityManager): void
    {
        $existingCapsulesRubriques = $this->capsuleRubriqueRepository->findAll();

        foreach ($existingCapsulesRubriques as $existingCapsuleRubrique) {
            $existingCapsuleRubrique->getRubriques()->clear();
        }

        $entityManager->flush();
    }

    /**
     * @param CapsuleRubrique[] $capsulesRubriques
     *
     * Importe une configuration complète de formulaires personnalisés
     * depuis un fichier JSON (généré via l'export de la configuration)
     */
    public function importConfiguration(EntityManagerInterface $entityManager, array $capsulesRubriques): void
    {
        $batchSize = 20; // Batch par rubrique (pas par champ)
        $counter = 0;

        // Précharge tous les types de champs pour éviter N+1 queries
        $typeChampMap = $this->preloadTypeChamps();

        foreach ($capsulesRubriques as $capsuleRubrique) {
            // Récupère les capsules rubriques statiques
            $managedCapsuleRubrique = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => $capsuleRubrique->getNomInterne()]);

            if (!$managedCapsuleRubrique instanceof CapsuleRubrique) {
                $this->logger->warning('Objet "{capsuleRubrique}" inconnu.', ['capsuleRubrique' => $capsuleRubrique->getNomInterne()]);
                continue;
            }

            // Conserve l'ID de la capsule pour re-fetch après clear
            $capsuleRubriqueId = $managedCapsuleRubrique->getId();

            foreach ($capsuleRubrique->getRubriques() as $rubrique) {
                // Re-récupère la capsule si nécessaire après un clear
                if (!$entityManager->contains($managedCapsuleRubrique)) {
                    $refetchedCapsule = $this->capsuleRubriqueRepository->find($capsuleRubriqueId);

                    if (!$refetchedCapsule instanceof CapsuleRubrique) {
                        $this->logger->error('Impossible de récupérer la capsule rubrique avec l\'ID {id}.', ['id' => $capsuleRubriqueId]);
                        continue;
                    }

                    $managedCapsuleRubrique = $refetchedCapsule;
                }

                // Crée la nouvelle rubrique
                $newRubrique = $this->createRubrique($entityManager, $rubrique, $managedCapsuleRubrique, false);

                if (null === $newRubrique) {
                    continue;
                }

                foreach ($rubrique->getChamps() as $champ) {
                    if ($champ->getParent() instanceof Champ) {
                        continue;
                    }

                    // Crée le nouveau champ, les champs enfants sont exclus
                    $newChamp = $this->createChamp($entityManager, $champ, $newRubrique, null, false, $typeChampMap);

                    if (null === $newChamp) {
                        continue;
                    }

                    if (!$champ->getEnfants()->isEmpty()) {
                        foreach ($champ->getEnfants() as $enfant) {
                            // Crée le nouveau champ enfant depuis le champ parent créé au préalable
                            $enfantNewChamp = $this->createChamp($entityManager, $enfant, $newRubrique, $newChamp, false, $typeChampMap);

                            if (null === $enfantNewChamp) {
                                continue;
                            }

                            $newChamp->addEnfant($enfantNewChamp);
                        }
                    }
                }

                // Batch flush après chaque rubrique complète (évite de détacher $newRubrique)
                ++$counter;
                if (0 === ($counter % $batchSize)) {
                    $entityManager->flush();
                    $entityManager->clear(); // Libère la mémoire
                }
            }
        }

        // Flush final pour les entités restantes
        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * @param bool $flush Flush immédiatement ou non (désactivé pour batch processing)
     */
    private function createRubrique(EntityManagerInterface $entityManager, Rubrique $exportedRubrique, CapsuleRubrique $capsuleRubrique, bool $flush = true): ?Rubrique
    {
        if (null === $exportedRubrique->getNom()) {
            $this->logger->warning('Le nom de la rubrique doit être renseigné.');

            return null;
        }

        $rubrique = new Rubrique();
        $rubrique->setNom($exportedRubrique->getNom());
        $rubrique->setDefaultDisplay($exportedRubrique->getDefaultDisplay());
        $rubrique->setIdx((int) $exportedRubrique->getIdx());
        $rubrique->setCapsuleRubrique($capsuleRubrique);

        $entityManager->persist($rubrique);

        if ($flush) {
            $entityManager->flush();
        }

        return $rubrique;
    }

    /**
     * @param bool                    $flush        Flush immédiatement ou non (désactivé pour batch processing)
     * @param array<string, int>|null $typeChampMap Précharge des IDs de types de champs pour éviter N+1
     */
    private function createChamp(EntityManagerInterface $entityManager, Champ $exportedChamp, Rubrique $rubrique, ?Champ $parent = null, bool $flush = true, ?array $typeChampMap = null): ?Champ
    {
        $champType = $exportedChamp->getType();

        if (null === $champType) {
            $this->logger->warning('Le type du champ doit être renseigné.');

            return null;
        }

        if (null === $exportedChamp->getNom()) {
            $this->logger->warning('Le nom du champ doit être renseigné.');

            return null;
        }

        // Utilise la précharge si disponible (getReference = proxy sans requête SQL)
        if (null !== $typeChampMap && isset($typeChampMap[$champType->getType()])) {
            $typeChampId = $typeChampMap[$champType->getType()];
            $managedType = $entityManager->getReference(TypeChamp::class, $typeChampId);
        } else {
            $managedType = $this->typeChampRepository->findOneBy(['type' => $champType->getType()]);
        }

        $champ = new Champ();
        $champ->setNom($exportedChamp->getNom());
        $champ->setType($managedType);
        $champ->setTableau($exportedChamp->getTableau());
        $champ->setIdx((int) $exportedChamp->getIdx());
        $champ->setRubrique($rubrique);

        if ($parent instanceof Champ) {
            $champ->setParent($parent);
        }

        $entityManager->persist($champ);

        foreach ($exportedChamp->getValeursListe() as $exportedValeurListe) {
            if (null === $exportedValeurListe->getValeur()) {
                $this->logger->warning('La valeur de la liste doit être renseignée.');

                continue;
            }

            $valeurListe = new ChampValeurListe();
            $valeurListe->setValeur($exportedValeurListe->getValeur());
            $valeurListe->setChamp($champ);

            $entityManager->persist($valeurListe);
            $champ->addValeursListe($valeurListe);
        }

        if ($flush) {
            $entityManager->flush();
        }

        return $champ;
    }

    /**
     * Précharge tous les types de champs en mémoire pour éviter N+1 queries.
     * Stocke les IDs pour éviter les problèmes de détachement après clear().
     *
     * @return array<string, int> Map indexée par le type vers l'ID
     */
    private function preloadTypeChamps(): array
    {
        $typeChamps = $this->typeChampRepository->findAll();
        $map = [];

        foreach ($typeChamps as $typeChamp) {
            $id = $typeChamp->getId();
            if (null !== $id) {
                $map[$typeChamp->getType()] = $id;
            }
        }

        return $map;
    }
}
