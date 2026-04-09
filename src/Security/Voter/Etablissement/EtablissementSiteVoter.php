<?php

namespace App\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Service\Voter\EtablissementVoterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementSiteVoter extends EtablissementVoter
{
    /** @var EtablissementVoterFactory */
    private $voterFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var ResourcesRepository */
    private $resourcesRepository;

    /** @var PrivilegesRepository */
    private $privilegesRepository;

    public function __construct(
        EtablissementVoterFactory $voterFactory,
        LoggerInterface $logger,
        ResourcesRepository $resourcesRepository,
        PrivilegesRepository $privilegesRepository
    ) {
        $this->voterFactory = $voterFactory;
        $this->logger = $logger;
        $this->resourcesRepository = $resourcesRepository;
        $this->privilegesRepository = $privilegesRepository;
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject)
            && parent::isSubjectGenreAllowed($subject, GenreEtablissement::SITE);
    }

    /**
     * L'accès à un site est accordé si l'accès à au moins un de ses fils est accordé.
     * Un site peut avoir des fils de différents genres (ERP, IGH, Habitation, etc.).
     * Si le site est vide, l'accès est accordé si l'utilisateur a au moins une ressource établissement.
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $etablissementsFils = $subject->getFils()->getValues();

        // Early return : Si le Site est vide, vérifier si l'utilisateur a au moins une ressource établissement
        if (0 === count($etablissementsFils)) {
            /** @var Utilisateur $user */
            $user = $token->getUser();

            return $this->userHasAnyEtablissementResource($user, $attribute);
        }

        // Site avec fils : Vérifier l'accès à au moins un fils
        foreach ($etablissementsFils as $etablissementFils) {
            try {
                $latestInformations = $etablissementFils->getLatestInformations();
            } catch (\OutOfBoundsException $e) {
                $this->logger->warning('Établissement fils sans informations', [
                    'etablissement_fils_id' => $etablissementFils->getId(),
                    'exception' => $e->getMessage(),
                ]);
                continue;
            }

            $genre = $latestInformations->getGenre();
            if (null === $genre) {
                $this->logger->warning('Établissement fils sans genre', [
                    'etablissement_fils_id' => $etablissementFils->getId(),
                ]);
                continue;
            }

            $genreId = $genre->getId();
            if (null === $genreId) {
                $this->logger->warning('Genre sans ID', [
                    'etablissement_fils_id' => $etablissementFils->getId(),
                ]);
                continue;
            }

            $voter = $this->voterFactory->getVoterForGenre($genreId);
            if (null === $voter) {
                $this->logger->warning('Aucun voter trouvé pour le genre', [
                    'etablissement_fils_id' => $etablissementFils->getId(),
                    'genre_id' => $genreId,
                ]);
                continue;
            }

            if (VoterInterface::ACCESS_GRANTED === $voter->vote($token, $etablissementFils, [$attribute])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a au moins une ressource permettant de gérer des établissements.
     * Utilisé pour permettre l'accès aux Sites vides afin de pouvoir y ajouter des fils.
     */
    private function userHasAnyEtablissementResource(Utilisateur $user, string $attribute): bool
    {
        $groupe = $user->getGroupe();
        if (null === $groupe) {
            return false;
        }

        $userPrivileges = $groupe->getPrivileges();
        $allEtablissementResources = $this->resourcesRepository->findRessourcesSpecialiseesEtablissement();

        foreach ($allEtablissementResources as $resource) {
            $privilege = $this->privilegesRepository->findOneBy([
                'name' => $attribute,
                'resource' => $resource,
            ]);

            if ($privilege && $userPrivileges->contains($privilege)) {
                return true; // L'utilisateur a au moins une ressource établissement
            }
        }

        return false;
    }
}
