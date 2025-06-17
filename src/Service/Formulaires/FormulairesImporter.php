<?php

namespace App\Service\Formulaires;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\ChampValeurListe;
use App\Entity\Rubrique;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\TypeChampRepository;
use Doctrine\ORM\EntityManagerInterface;

final class FormulairesImporter
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CapsuleRubriqueRepository */
    private $capsuleRubriqueRepository;

    /** @var TypeChampRepository */
    private $typeChampRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CapsuleRubriqueRepository $capsuleRubriqueRepository,
        TypeChampRepository $typeChampRepository
    ) {
        $this->entityManager = $entityManager;
        $this->capsuleRubriqueRepository = $capsuleRubriqueRepository;
        $this->typeChampRepository = $typeChampRepository;
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
     * @param \App\Entity\CapsuleRubrique[] $capsulesRubriques
     *
     * Importe une configuration complète de formulaires personnalisés
     * depuis un fichier JSON (généré via l'export de la configuration).
     */
    public function importConfiguration(EntityManagerInterface $entityManager, array $capsulesRubriques): void
    {
        foreach ($capsulesRubriques as $capsuleRubrique) {
            // Récupère les capsules rubriques statiques
            $managedCapsuleRubrique = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => $capsuleRubrique->getNomInterne()]);

            foreach ($capsuleRubrique->getRubriques() as $rubrique) {
                // Crée la nouvelle rubrique
                $newRubrique = $this->createRubrique($entityManager, $rubrique, $managedCapsuleRubrique);

                foreach ($rubrique->getChamps() as $champ) {
                    if ($champ->getParent() instanceof Champ) {
                        continue;
                    }

                    // Crée le nouveau champ, les champs enfants sont exclus
                    $newChamp = $this->createChamp($entityManager, $champ, $newRubrique);

                    if (!$champ->getEnfants()->isEmpty()) {
                        foreach ($champ->getEnfants() as $enfant) {
                            // Crée le nouveau champ enfant depuis le champ parent créé au préalable
                            $enfantNewChamp = $this->createChamp($entityManager, $enfant, $newRubrique, $newChamp);

                            $newChamp->addEnfant($enfantNewChamp);
                        }
                    }

                    $entityManager->flush();
                }
            }
        }
    }

    private function createRubrique(EntityManagerInterface $entityManager, Rubrique $exportedRubrique, CapsuleRubrique $capsuleRubrique): Rubrique
    {
        $rubrique = new Rubrique();
        $rubrique->setNom($exportedRubrique->getNom());
        $rubrique->setDefaultDisplay($exportedRubrique->getDefaultDisplay());
        $rubrique->setIdx($exportedRubrique->getIdx());
        $rubrique->setCapsuleRubrique($capsuleRubrique);

        $entityManager->persist($rubrique);
        $entityManager->flush();

        return $rubrique;
    }

    private function createChamp(EntityManagerInterface $entityManager, Champ $exportedChamp, Rubrique $rubrique, ?Champ $parent = null): Champ
    {
        $managedType = $this->typeChampRepository->findOneBy(['type' => $exportedChamp->getType()->getType()]);

        $champ = new Champ();
        $champ->setNom($exportedChamp->getNom());
        $champ->setType($managedType);
        $champ->setTableau($exportedChamp->getTableau());
        $champ->setIdx($exportedChamp->getIdx());
        $champ->setRubrique($rubrique);

        if ($parent instanceof Champ) {
            $champ->setParent($parent);
        }

        $entityManager->persist($champ);
        $entityManager->flush();

        foreach ($exportedChamp->getValeursListe() as $exportedValeurListe) {
            $valeurListe = new ChampValeurListe();
            $valeurListe->setValeur($exportedValeurListe->getValeur());
            $valeurListe->setChamp($champ);

            $entityManager->persist($valeurListe);
            $entityManager->flush();

            $champ->addValeursListe($valeurListe);
        }
        $entityManager->flush();

        return $champ;
    }
}
