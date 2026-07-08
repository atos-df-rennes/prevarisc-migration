<?php

namespace App\Service\Voter;

use App\Entity\Etablissement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

    public function __construct(EtablissementVoterFactory $voterFactory, LoggerInterface $logger)
    {
        $this->voterFactory = $voterFactory;
        $this->logger = $logger;
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
                $this->logger->warning('Aucun voter trouvé pour le genre', [
                    'etablissement_id' => $etablissement->getId(),
                    'genre_id' => $genreId,
                ]);
                continue;
            }

            if (VoterInterface::ACCESS_GRANTED === $voter->vote($token, $etablissement, [$attribute])) {
                return true;
            }
        }

        return false;
    }
}
