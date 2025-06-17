<?php

namespace App\Service\Formulaires;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\ChampValeurListe;
use App\Entity\Rubrique;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\TypeChampRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class FormulairesImporter
{
    /** @var CapsuleRubriqueRepository */
    private $capsuleRubriqueRepository;

    /** @var TypeChampRepository */
    private $typeChampRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CapsuleRubriqueRepository $capsuleRubriqueRepository,
        TypeChampRepository $typeChampRepository,
        LoggerInterface $logger
    ) {
        $this->capsuleRubriqueRepository = $capsuleRubriqueRepository;
        $this->typeChampRepository = $typeChampRepository;
        $this->logger = $logger;
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
        foreach ($capsulesRubriques as $capsuleRubrique) {
            // Récupère les capsules rubriques statiques
            $managedCapsuleRubrique = $this->capsuleRubriqueRepository->findOneBy(['nomInterne' => $capsuleRubrique->getNomInterne()]);

            if (!$managedCapsuleRubrique instanceof CapsuleRubrique) {
                $this->logger->warning('Objet "{capsuleRubrique}" inconnu.', ['capsuleRubrique' => $capsuleRubrique->getNomInterne()]);
                continue;
            }

            foreach ($capsuleRubrique->getRubriques() as $rubrique) {
                // Crée la nouvelle rubrique
                $newRubrique = $this->createRubrique($entityManager, $rubrique, $managedCapsuleRubrique);

                if (null === $newRubrique) {
                    continue;
                }

                foreach ($rubrique->getChamps() as $champ) {
                    if ($champ->getParent() instanceof Champ) {
                        continue;
                    }

                    // Crée le nouveau champ, les champs enfants sont exclus
                    $newChamp = $this->createChamp($entityManager, $champ, $newRubrique);

                    if (null === $newChamp) {
                        continue;
                    }

                    if (!$champ->getEnfants()->isEmpty()) {
                        foreach ($champ->getEnfants() as $enfant) {
                            // Crée le nouveau champ enfant depuis le champ parent créé au préalable
                            $enfantNewChamp = $this->createChamp($entityManager, $enfant, $newRubrique, $newChamp);

                            if (null === $enfantNewChamp) {
                                continue;
                            }

                            $newChamp->addEnfant($enfantNewChamp);
                        }
                    }

                    $entityManager->flush();
                }
            }
        }
    }

    private function createRubrique(EntityManagerInterface $entityManager, Rubrique $exportedRubrique, CapsuleRubrique $capsuleRubrique): ?Rubrique
    {
        if (null === $exportedRubrique->getNom()) {
            $this->logger->warning('Le nom de la rubrique doit être renseigné.');

            return null;
        }

        $rubrique = new Rubrique();
        $rubrique->setNom($exportedRubrique->getNom());
        $rubrique->setDefaultDisplay($exportedRubrique->getDefaultDisplay());
        $rubrique->setIdx($exportedRubrique->getIdx());
        $rubrique->setCapsuleRubrique($capsuleRubrique);

        $entityManager->persist($rubrique);
        $entityManager->flush();

        return $rubrique;
    }

    private function createChamp(EntityManagerInterface $entityManager, Champ $exportedChamp, Rubrique $rubrique, ?Champ $parent = null): ?Champ
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

        $managedType = $this->typeChampRepository->findOneBy(['type' => $champType->getType()]);

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
            if (null === $exportedValeurListe->getValeur()) {
                $this->logger->warning('La valeur de la liste doit être renseignée.');

                continue;
            }

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
