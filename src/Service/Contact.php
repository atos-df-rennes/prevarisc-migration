<?php

namespace App\Service;

use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use App\Repository\CommissionRepository;
use App\Repository\CommuneRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Repository\GroupementRepository;

final class Contact
{
    public const ETABLISSEMENT = 'etablissement';

    public const DOSSIER = 'dossier';

    public const GROUPEMENT = 'groupement';

    public const COMMISSION = 'commission';

    public const COMMUNE = 'commune';

    private EtablissementRepository $etablissementRepository;

    private DossierRepository $dossierRepository;

    private GroupementRepository $groupementRepository;

    private CommissionRepository $commissionRepository;

    private CommuneRepository $communeRepository;

    /** @var array<string, HasContacts> */
    private array $repositories;

    public function __construct(
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        GroupementRepository $groupementRepository,
        CommissionRepository $commissionRepository,
        CommuneRepository $communeRepository
    ) {
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->groupementRepository = $groupementRepository;
        $this->commissionRepository = $commissionRepository;
        $this->communeRepository = $communeRepository;
        $this->repositories = [
            self::ETABLISSEMENT => $this->etablissementRepository,
            self::DOSSIER => $this->dossierRepository,
            self::GROUPEMENT => $this->groupementRepository,
            self::COMMISSION => $this->commissionRepository,
            self::COMMUNE => $this->communeRepository,
        ];
    }

    /**
     * Détermine si le contact est utilisé par d'autres entités.
     */
    public function isUsed(UtilisateurInformations $contact, string $elementLie): bool
    {
        foreach ($this->repositories as $element => $repository) {
            $minimalValidCount = $elementLie === $element ? 1 : 0;

            if ($repository->findNumberByContacts($contact) > $minimalValidCount) {
                return true;
            }
        }

        return false;
    }
}
