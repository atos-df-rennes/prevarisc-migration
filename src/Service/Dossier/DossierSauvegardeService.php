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
use App\Service\DossierFields;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DossierSauvegardeService
{
    public function __construct(private readonly DossierDonnantAvisService $dossierDonnantAvisService, private readonly AlerteService $alerteService, private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly FlashBagInterface $flashBag, private readonly UrlGeneratorInterface $urlGenerator, private readonly DossierFields $dossierFields, private readonly TagAwareCacheInterface $searchCache)
    {
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
        $avisCommission = $dossier->getAvisCommission() instanceof Avis ? $dossier->getAvisCommission()->getId() : null;
        $avisRenseigne = in_array($avisCommission, [AvisConstant::FAVORABLE, AvisConstant::DEFAVORABLE], true);

        if ($dossier->donneAvis() && $avisRenseigne) {
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
                htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8'),
                $lienAlerter
            ));
        }
    }

    /**
     * Construit l'URL de redirection après sauvegarde d'un dossier.
     *
     * Passe toujours par le legacy pour vider le cache de recherche (cacheSearch).
     * Si des établissements ont été modifiés, passe par la route de vidage de cache dossier
     * Construit l'URL de redirection après sauvegarde d'un dossier.
     *
     * Invalide le cache de recherche Symfony (tag "search"), puis retourne la cible
     * directement lorsqu'aucun établissement n'est concerné.
     * Lorsque des établissements sont modifiés, le cache Symfony est également invalidé
     * et le passage par le legacy permet de vider le cache individuel de chaque établissement.
     *
     * @param int[]       $etablissementsIds IDs des établissements modifiés
     * @param string|null $target            URL cible (null = dossier_index par défaut)
     */
    public function construireUrlRedirection(Dossier $dossier, array $etablissementsIds, ?string $target = null): string
    {
        $target ??= $this->urlGenerator->generate('dossier_index', ['id' => $dossier->getId()]);

        $this->searchCache->invalidateTags(['search']);

        if ([] === $etablissementsIds) {
            return $target;
        }

        // @fixme: variable non utilisée mais conservée une fois les caches entité implémentés à nouveau
        $etablissementsJson = json_encode($etablissementsIds);
        if (false === $etablissementsJson) {
            throw new \RuntimeException('Impossible d\'encoder les IDs établissements en JSON');
        }

        return $target;
    }

    /**
     * Nullifie les champs non applicables à la nature du dossier.
     *
     * Reproduit le comportement legacy (saveAction, lignes 915-917) applicable en
     * création ET en édition : si le champ 'objet' n'est pas dans la liste des
     * champs affichables pour la nature, OBJET_DOSSIER est forcé à null.
     * Doit être appelé avant le persist, après le handleRequest du formulaire.
     */
    public function nullifierChampsNonApplicables(Dossier $dossier): void
    {
        if (!$this->dossierFields->shouldDisplayField($dossier, 'objet')) {
            $dossier->setObjet(null);
        }
    }
}
