<?php

namespace App\Security\Voter;

use App\Entity\Etablissement;
use App\Service\Voter\EtablissementVoterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementSiteVoter extends EtablissementVoter
{
    public const ID_SITE = 1;

    /** @var EtablissementVoterFactory */
    private $voterFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EtablissementVoterFactory $voterFactory, LoggerInterface $logger)
    {
        $this->voterFactory = $voterFactory;
        $this->logger = $logger;
    }

    protected function supports($attribute, $subject): bool
    {
        return parent::supports($attribute, $subject)
            && parent::isSubjectGenreAllowed($subject, self::ID_SITE);
    }

    /**
     * L'accès à un site est accordé si l'accès à au moins un de ses fils est accordé.
     * Un site peut avoir des fils de différents genres (ERP, IGH, Habitation, etc.).
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!parent::voteOnAttribute($attribute, $subject, $token)) {
            return false;
        }

        /** @var Etablissement $subject */
        $etablissementsFils = $subject->getFils()->getValues();

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
}
