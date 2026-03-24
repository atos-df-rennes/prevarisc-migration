<?php

declare(strict_types=1);

namespace App\Service\Dossier;

use App\Constant\Avis as AvisConstant;
use App\Constant\DossierNature;
use App\Constant\StatutEtablissement;
use App\Constant\TypeChangement;
use App\Entity\Avis;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Service\Alerte\AlerteService;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DossierSauvegardeService
{
    /** @var DossierDonnantAvisService */
    private $dossierDonnantAvisService;

    /** @var AlerteService */
    private $alerteService;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        DossierDonnantAvisService $dossierDonnantAvisService,
        AlerteService $alerteService,
        AuthorizationCheckerInterface $authorizationChecker,
        FlashBagInterface $flashBag,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->dossierDonnantAvisService = $dossierDonnantAvisService;
        $this->alerteService = $alerteService;
        $this->authorizationChecker = $authorizationChecker;
        $this->flashBag = $flashBag;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Gère l'affectation/désaffectation du dossier donnant avis sur les établissements.
     *
     * En création (ancienneNature = null), seule l'affectation est possible.
     * En édition, désaffecte si la nouvelle nature ne donne plus avis.
     *
     * @return int[] IDs des établissements modifiés
     */
    public function gererDossierDonnantAvis(
        Dossier $dossier,
        bool $repercuterAvis,
        ?DossierNatureListe $ancienneNature = null
    ): array {
        if ($dossier->donneAvis()) {
            return $this->dossierDonnantAvisService->affecterDossierDonnantAvis($dossier, $repercuterAvis);
        }

        if (null !== $ancienneNature) {
            return $this->dossierDonnantAvisService->desaffecterDossierDonnantAvis($dossier, $ancienneNature);
        }

        return [];
    }

    /**
     * Ajoute un flash d'avertissement si une VAO favorable concerne un établissement en statut "Projet".
     */
    public function genererAlertesVAO(Dossier $dossier): void
    {
        $natureId = $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null;
        $avisCommission = $dossier->getAvisCommission() instanceof Avis ? $dossier->getAvisCommission()->getId() : null;

        if (
            AvisConstant::FAVORABLE !== $avisCommission
            || !in_array($natureId, DossierNature::VISITE_AVANT_OUVERTURE, true)
        ) {
            return;
        }

        foreach ($dossier->getEtablissements() as $etablissement) {
            $informations = $etablissement->getLatestInformations();
            $statut = $informations->getStatut();
            if (!$statut) {
                continue;
            }
            if (StatutEtablissement::PROJET !== $statut->getId()) {
                continue;
            }

            $libelle = $informations->getLibelle() ?: 'Établissement #'.$etablissement->getId();
            $informationsEtablissement = $this->urlGenerator->generate(
                'etablissement_informations',
                ['id' => $etablissement->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->flashBag->add('warning', sprintf(
                '<strong>Avertissement</strong><br>'.
                'La visite d\'avant ouverture étant favorable, vous devriez passer le statut de l\'établissement '.
                '<a title="Ouvrir" href="%s">%s</a> à \'ouvert\' (statut actuellement à \'projet\').',
                $informationsEtablissement,
                htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8')
            ));
        }
    }

    /**
     * Ajoute des flash d'alerte email pour chaque établissement dont l'avis a changé.
     *
     * Compare le nouvel avis (après sauvegarde) avec l'ancien avis capturé avant le formulaire.
     * N'émet les alertes que si les droits 'alerte_avis' sont accordés.
     *
     * @param int[]                 $etablissementsIds IDs des établissements modifiés
     * @param array<int, Avis|null> $anciensAvis       Avis capturés avant handleRequest
     */
    public function genererAlertesEmailAvis(
        Dossier $dossier,
        array $etablissementsIds,
        array $anciensAvis,
        bool $mailEnabled
    ): void {
        if (!$mailEnabled || [] === $etablissementsIds || !$this->authorizationChecker->isGranted('alerte_avis')) {
            return;
        }

        foreach ($dossier->getEtablissements() as $etablissement) {
            $etablissementId = $etablissement->getId();

            if (!in_array($etablissementId, $etablissementsIds, true)) {
                continue;
            }

            $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();
            $nouvelAvis = $dossierDonnantAvis ? $dossierDonnantAvis->getAvisCommission() : null;
            $ancienAvis = $anciensAvis[$etablissementId] ?? null;

            if ($ancienAvis === $nouvelAvis) {
                continue;
            }

            $informations = $etablissement->getLatestInformations();
            $libelle = $informations->getLibelle() ?: 'Établissement #'.$etablissementId;
            $lienAlerter = $this->alerteService->getLink(TypeChangement::AVIS, $etablissementId);

            $this->flashBag->add('success', sprintf(
                '<p>L\'établissement %s a bien été mis à jour.</p>%s',
                $libelle,
                $lienAlerter
            ));
        }
    }

    /**
     * Construit l'URL de redirection après sauvegarde d'un dossier.
     *
     * Si des établissements ont été modifiés, renvoie vers le legacy pour vider leur cache.
     * Sinon, renvoie directement vers la page du dossier.
     *
     * @param int[] $etablissementsIds IDs des établissements modifiés
     */
    public function construireUrlRedirection(Dossier $dossier, array $etablissementsIds): string
    {
        if ([] === $etablissementsIds) {
            return $this->urlGenerator->generate('dossier_index', ['id' => $dossier->getId()]);
        }

        $etablissementsJson = json_encode($etablissementsIds);
        if (false === $etablissementsJson) {
            throw new \RuntimeException('Impossible d\'encoder les IDs établissements en JSON');
        }

        return sprintf(
            '/legacy/dossier/vider-cache?id=%d&etablissements=%s',
            $dossier->getId(),
            urlencode($etablissementsJson)
        );
    }
}
