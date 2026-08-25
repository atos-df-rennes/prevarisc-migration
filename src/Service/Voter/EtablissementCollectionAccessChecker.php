<?php

namespace App\Service\Voter;

use App\Entity\Etablissement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Vérifie si au moins un établissement dans une collection est accessible avec un privilège donné.
 *
 * Cette logique est partagée entre EtablissementSiteVoter (accès aux fils d'un site)
 * et DossierVoter (contrôle des établissements liés avant d'accorder l'accès au dossier).
 */
class EtablissementCollectionAccessChecker
{
    /** @var EtablissementVoterFactory */
    private $voterFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    public function __construct(EtablissementVoterFactory $voterFactory, LoggerInterface $logger, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->voterFactory = $voterFactory;
        $this->logger = $logger;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Retourne true si au moins un établissement de la collection est accessible avec le privilège donné.
     *
     * @param iterable<Etablissement> $etablissements
     */
    public function hasAtLeastOneAccessible(iterable $etablissements, string $attribute, TokenInterface $token): bool
    {
        foreach ($etablissements as $etablissement) {
            try {
                $latestInformations = $etablissement->getLatestInformations();
            } catch (\OutOfBoundsException $e) {
                $this->logger->warning('Établissement sans informations', [
                    'etablissement_id' => $etablissement->getId(),
                    'exception' => $e->getMessage(),
                ]);
                continue;
            }

            $genre = $latestInformations->getGenre();
            if (null === $genre) {
                $this->logger->warning('Établissement sans genre', [
                    'etablissement_id' => $etablissement->getId(),
                ]);
                continue;
            }

            $genreId = $genre->getId();
            if (null === $genreId) {
                $this->logger->warning('Genre sans ID', [
                    'etablissement_id' => $etablissement->getId(),
                ]);
                continue;
            }

            $voter = $this->voterFactory->getVoterForGenre($genreId);
            if (null === $voter) {
                // Certains genres (ex. Site, genre 1) n'ont pas de voter dédié dans la factory
                // (pour éviter une dépendance circulaire Factory -> EtablissementSiteVoter -> ce service).
                // On délègue à la chaîne de voters Symfony complète via l'authorization checker.
                $this->logger->info('Aucun voter dédié dans la factory pour le genre, délégation à l\'authorization checker', [
                    'etablissement_id' => $etablissement->getId(),
                    'genre_id' => $genreId,
                ]);

                if ($this->authorizationChecker->isGranted($attribute, $etablissement)) {
                    return true;
                }

                continue;
            }

            if (VoterInterface::ACCESS_GRANTED === $voter->vote($token, $etablissement, [$attribute])) {
                return true;
            }
        }

        return false;
    }
}
