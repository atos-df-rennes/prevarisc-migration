<?php

namespace App\Service;

use App\Entity\News;
use App\Entity\Utilisateur;
use App\Repository\GroupeRepository;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion des messages/news du tableau de bord.
 * Port direct de Service_Feed (legacy).
 */
class FeedService
{
    private EntityManagerInterface $entityManager;

    private NewsRepository $newsRepository;

    private GroupeRepository $groupeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        NewsRepository $newsRepository,
        GroupeRepository $groupeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->newsRepository = $newsRepository;
        $this->groupeRepository = $groupeRepository;
    }

    /**
     * Récupère les messages visibles pour le groupe de l'utilisateur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFeeds(Utilisateur $user, int $count = 5): array
    {
        $groupe = $user->getGroupe();
        if (null === $groupe) {
            return [];
        }

        return $this->newsRepository->findFeedsByGroupeId((int) $groupe->getId(), $count);
    }

    /**
     * Compte les messages visibles pour le groupe de l'utilisateur.
     */
    public function getFeedsCount(Utilisateur $user): int
    {
        $groupe = $user->getGroupe();
        if (null === $groupe) {
            return 0;
        }

        return $this->newsRepository->countFeedsByGroupeId((int) $groupe->getId());
    }

    /**
     * Ajoute un message visible par les groupes spécifiés.
     * Port direct : ID_NEWS = time() (timestamp Unix), pas d'auto-incrément.
     *
     * @param int[] $groupeIds
     */
    public function addMessage(string $type, string $texte, Utilisateur $author, array $groupeIds): void
    {
        $news = new News();
        $news->setId(time());
        $news->setType($type);
        $news->setTexte($texte);
        $news->setUtilisateur($author);

        $this->entityManager->persist($news);

        foreach ($groupeIds as $groupeId) {
            $groupe = $this->groupeRepository->find($groupeId);
            if (null !== $groupe) {
                $groupe->addNews($news);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Retourne un message par son identifiant, ou null s'il n'existe pas.
     */
    public function findMessage(int $id): ?News
    {
        return $this->newsRepository->find($id);
    }

    /**
     * Supprime un message.
     */
    public function deleteMessage(int $id): void
    {
        $news = $this->newsRepository->find($id);
        if (null !== $news) {
            $this->entityManager->remove($news);
            $this->entityManager->flush();
        }
    }
}
