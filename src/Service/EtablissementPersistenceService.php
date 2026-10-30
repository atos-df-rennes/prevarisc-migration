<?php

namespace App\Service;

use App\Constant\GenreEtablissement;
use App\Entity\AdresseRue;
use App\Entity\Commune;
use App\Entity\Etablissement as EtablissementEntity;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Entity\EtablissementInformationsPlan;
use App\Entity\EtablissementInformationsRubriques;
use App\Entity\EtablissementInformationsTypesActivitesSecondaires;
use App\Entity\EtablissementType;
use App\Entity\TypePlan;
use App\Entity\Utilisateur;
use App\Repository\EtablissementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de persistance des données associées à un établissement.
 *
 * Encapsule les opérations de persistance communes entre la création et la mise à jour
 * d'un établissement (préventionnistes, rubriques, plans, activités secondaires, adresses,
 * établissements liés et père).
 */
final readonly class EtablissementPersistenceService
{
    public function __construct(private EtablissementRepository $etablissementRepository)
    {
    }

    /**
     * Persiste les préventionnistes associés à un établissement.
     *
     * @param array<string, mixed> $data
     */
    public function persistPreventionnistes(EtablissementInformations $informations, array $data, EntityManagerInterface $em): void
    {
        if (!empty($data['ID_UTILISATEUR']) && is_array($data['ID_UTILISATEUR'])) {
            foreach ($data['ID_UTILISATEUR'] as $idPreventionniste) {
                $idPreventionniste = self::toInt($idPreventionniste);
                if ($idPreventionniste > 0) {
                    /** @var Utilisateur $prev */
                    $prev = $em->getReference(Utilisateur::class, $idPreventionniste);
                    $informations->addPreventionniste($prev);
                }
            }
        }
    }

    /**
     * Persiste les rubriques ICPE (BUP uniquement).
     *
     * @param array<string, mixed> $data
     */
    public function persistRubriques(int $idGenre, EtablissementInformations $informations, array $data, EntityManagerInterface $em): void
    {
        if (GenreEtablissement::BUP === $idGenre && !empty($data['RUBRIQUES']) && is_array($data['RUBRIQUES'])) {
            foreach ($data['RUBRIQUES'] as $key => $rubriqueData) {
                if (0 === self::toInt($key)) {
                    continue;
                }

                if (!is_array($rubriqueData)) {
                    continue;
                }

                $rubrique = new EtablissementInformationsRubriques();
                $rubrique->setRubrique(self::toInt($rubriqueData['ID_RUBRIQUE'] ?? 0));
                $rubrique->setNumero(self::toInt($rubriqueData['NUMERO_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? 0));
                $rubrique->setValeur(self::toFloat($rubriqueData['VALEUR_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? 0.0));
                $rubrique->setNom(self::toString($rubriqueData['NOM_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? ''));
                $rubrique->setClassement(self::toString($rubriqueData['CLASSEMENT_ETABLISSEMENTINFORMATIONSRUBRIQUE'] ?? ''));
                $rubrique->setEtablissementInformations($informations);
                $em->persist($rubrique);
            }
        }
    }

    /**
     * Persiste les plans (ERP, Cellule, IGH, BUP, Camping, Manifestation temporaire, IOP).
     *
     * @param array<string, mixed> $data
     */
    public function persistPlans(int $idGenre, EtablissementInformations $informations, array $data, EntityManagerInterface $em): void
    {
        $genresAvecPlans = [
            GenreEtablissement::ERP,
            GenreEtablissement::CELLULE,
            GenreEtablissement::IGH,
            GenreEtablissement::BUP,
            GenreEtablissement::CAMPING,
            GenreEtablissement::MANIFESTATION_TEMPORAIRE,
            GenreEtablissement::IOP,
        ];
        if (in_array($idGenre, $genresAvecPlans, true) && !empty($data['PLANS']) && is_array($data['PLANS'])) {
            foreach ($data['PLANS'] as $key => $planData) {
                if (0 === self::toInt($key)) {
                    continue;
                }

                if (!is_array($planData)) {
                    continue;
                }

                if (empty($planData['ID_TYPEPLAN'])) {
                    continue;
                }

                $plan = new EtablissementInformationsPlan();
                /** @var TypePlan $typePlan */
                $typePlan = $em->getReference(TypePlan::class, self::toInt($planData['ID_TYPEPLAN']));
                $plan->setType($typePlan);
                $rawNumPlan = $planData['NUMERO_ETABLISSEMENTPLAN'] ?? null;
                $plan->setNumero(null !== $rawNumPlan ? self::toString($rawNumPlan) : null);
                $rawDatePlan = $planData['DATE_ETABLISSEMENTPLAN'] ?? '';
                $datePlan = ('' !== self::toString($rawDatePlan))
                    ? new \DateTime(self::toString($rawDatePlan))
                    : new \DateTime('today');
                $plan->setDate($datePlan);
                $plan->setAMettreAJour(!empty($planData['MISEAJOUR_ETABLISSEMENTPLAN']));
                $plan->setEtablissementInformations($informations);
                $em->persist($plan);
            }
        }
    }

    /**
     * Persiste les activités secondaires (ERP et Cellule uniquement).
     *
     * @param array<string, mixed> $data
     */
    public function persistActivitesSecondaires(int $idGenre, EtablissementInformations $informations, array $data, EntityManagerInterface $em): void
    {
        if (in_array($idGenre, [GenreEtablissement::ERP, GenreEtablissement::CELLULE], true)
            && !empty($data['TYPES_ACTIVITES_SECONDAIRES'])
            && is_array($data['TYPES_ACTIVITES_SECONDAIRES'])
        ) {
            foreach ($data['TYPES_ACTIVITES_SECONDAIRES'] as $key => $taData) {
                if (0 === self::toInt($key)) {
                    continue;
                }

                if (!is_array($taData)) {
                    continue;
                }

                $idTypeSecondaire = self::toInt($taData['ID_TYPE_SECONDAIRE'] ?? 0);
                $idActiviteSecondaire = self::toInt($taData['ID_TYPEACTIVITE_SECONDAIRE'] ?? 0);
                if (0 === $idTypeSecondaire) {
                    continue;
                }

                if (0 === $idActiviteSecondaire) {
                    continue;
                }

                $ta = new EtablissementInformationsTypesActivitesSecondaires();
                /** @var EtablissementType $typeSecondaire */
                $typeSecondaire = $em->getReference(EtablissementType::class, $idTypeSecondaire);
                /** @var EtablissementActivite $activiteSecondaire */
                $activiteSecondaire = $em->getReference(EtablissementActivite::class, $idActiviteSecondaire);
                $ta->setType($typeSecondaire);
                $ta->setActivite($activiteSecondaire);
                $ta->setEtablissementInformations($informations);
                $em->persist($ta);
            }
        }
    }

    /**
     * Persiste les adresses (ERP, Habitation, IGH, BUP, Camping, Manifestation, IOP, Zone).
     *
     * @param array<string, mixed> $data
     */
    public function persistAdresses(int $idGenre, EtablissementEntity $etablissement, array $data, EntityManagerInterface $em): void
    {
        $genresAvecAdresse = [
            GenreEtablissement::ERP,
            GenreEtablissement::HABITATION,
            GenreEtablissement::IGH,
            GenreEtablissement::BUP,
            GenreEtablissement::CAMPING,
            GenreEtablissement::MANIFESTATION_TEMPORAIRE,
            GenreEtablissement::IOP,
            GenreEtablissement::ZONE,
        ];
        if (in_array($idGenre, $genresAvecAdresse, true) && !empty($data['ADRESSES']) && is_array($data['ADRESSES'])) {
            foreach ($data['ADRESSES'] as $key => $adresseData) {
                if (0 === self::toInt($key)) {
                    continue;
                }

                if (!is_array($adresseData)) {
                    continue;
                }

                $rawRueId = self::toInt($adresseData['ID_RUE'] ?? 0);
                if ($rawRueId <= 0) {
                    continue;
                }

                // F7 : éviter getReference(Commune, '') qui génère une FK invalide
                $rawInsee = self::toString($adresseData['NUMINSEE_COMMUNE'] ?? '');
                if ('' === $rawInsee) {
                    continue;
                }

                $adresse = new EtablissementAdresse();
                /** @var AdresseRue $rue */
                $rue = $em->getReference(AdresseRue::class, $rawRueId);
                $adresse->setRue($rue);
                /** @var Commune $commune */
                $commune = $em->getReference(Commune::class, $rawInsee);
                $adresse->setCommune($commune);
                $adresse->setEtablissement($etablissement);
                $rawNum = self::toString($adresseData['NUMERO_ADRESSE'] ?? '');
                $adresse->setNumero('' !== $rawNum ? $rawNum : null);
                $rawCompl = self::toString($adresseData['COMPLEMENT_ADRESSE'] ?? '');
                $adresse->setComplement('' !== $rawCompl ? $rawCompl : null);
                $rawLon = $adresseData['LON_ETABLISSEMENTADRESSE'] ?? '';
                $adresse->setLongitude(!empty($rawLon) ? self::toFloat($rawLon) : null);
                $rawLat = $adresseData['LAT_ETABLISSEMENTADRESSE'] ?? '';
                $adresse->setLatitude(!empty($rawLat) ? self::toFloat($rawLat) : null);
                $em->persist($adresse);
            }
        }
    }

    /**
     * Persiste les établissements fils et valide la compatibilité des genres.
     *
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException si la compatibilité des genres n'est pas respectée
     */
    public function persistEtablissementsLies(int $idGenre, EtablissementEntity $etablissement, array $data): void
    {
        if (!empty($data['ID_FILS_ETABLISSEMENT']) && is_array($data['ID_FILS_ETABLISSEMENT'])) {
            foreach ($data['ID_FILS_ETABLISSEMENT'] as $idFils) {
                $idFils = self::toInt($idFils);
                if ($idFils <= 0) {
                    continue;
                }

                $fils = $this->etablissementRepository->find($idFils);
                if (null === $fils) {
                    continue;
                }

                $filsGenre = $fils->getLatestInformations()->getGenre();
                $filsGenreId = $filsGenre?->getId();

                // Validations de compatibilité genre
                if (GenreEtablissement::SITE === $idGenre
                    && !in_array($filsGenreId, [GenreEtablissement::ERP, GenreEtablissement::HABITATION, GenreEtablissement::IGH, GenreEtablissement::BUP, GenreEtablissement::CAMPING, GenreEtablissement::MANIFESTATION_TEMPORAIRE, GenreEtablissement::IOP], true)
                ) {
                    throw new \InvalidArgumentException("L'établissement enfant n'est pas compatible avec un site.", 500);
                }

                if (in_array($idGenre, [GenreEtablissement::ERP, GenreEtablissement::IGH], true)
                    && GenreEtablissement::CELLULE !== $filsGenreId
                ) {
                    throw new \InvalidArgumentException('Un ERP ou IGH ne peut contenir que des cellules.', 500);
                }

                if (GenreEtablissement::SITE === $filsGenreId) {
                    throw new \InvalidArgumentException("Un site ne peut pas être enfant d'un autre établissement.", 500);
                }

                $etablissement->addFils($fils);
            }
        }
    }

    /**
     * Persiste le père de l'établissement et valide la compatibilité des genres.
     *
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException si la compatibilité des genres n'est pas respectée
     */
    public function persistPere(int $idGenre, EtablissementEntity $etablissement, array $data): void
    {
        if (!empty($data['ID_PERE'])) {
            $idPere = self::toInt($data['ID_PERE']);
            $pere = $this->etablissementRepository->find($idPere);
            if (null !== $pere) {
                $pereGenre = $pere->getLatestInformations()->getGenre();
                $pereGenreId = $pereGenre?->getId();

                if (in_array($idGenre, [GenreEtablissement::ERP, GenreEtablissement::HABITATION, GenreEtablissement::IGH, GenreEtablissement::BUP, GenreEtablissement::CAMPING, GenreEtablissement::MANIFESTATION_TEMPORAIRE, GenreEtablissement::IOP], true)
                    && GenreEtablissement::SITE !== $pereGenreId
                ) {
                    throw new \InvalidArgumentException("L'établissement père doit être un site.", 500);
                }

                if (GenreEtablissement::CELLULE === $idGenre
                    && !in_array($pereGenreId, [GenreEtablissement::ERP, GenreEtablissement::IGH], true)
                ) {
                    throw new \InvalidArgumentException('Une cellule doit avoir pour père un ERP ou un IGH.', 500);
                }

                if (GenreEtablissement::SITE === $idGenre) {
                    throw new \InvalidArgumentException('Un site ne peut pas avoir de père.', 500);
                }

                $etablissement->addParent($pere);
            }
        }
    }

    /**
     * @param mixed $val
     */
    private static function toInt($val, int $default = 0): int
    {
        return is_scalar($val) ? (int) $val : $default;
    }

    /**
     * @param mixed $val
     */
    private static function toString($val, string $default = ''): string
    {
        return is_scalar($val) ? (string) $val : $default;
    }

    /**
     * @param mixed $val
     */
    private static function toFloat($val, float $default = 0.0): float
    {
        return is_scalar($val) ? (float) $val : $default;
    }
}
