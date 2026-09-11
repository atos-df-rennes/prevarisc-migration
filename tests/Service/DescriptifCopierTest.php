<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Constant\TypeChamp as TypeChampConstant;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Rubrique;
use App\Entity\TypeChamp;
use App\Entity\Valeur;
use App\Service\DescriptifCopier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Teste la logique de copie bidirectionnelle du service DescriptifCopier.
 *
 * Couvre :
 * - Matching de rubriques et champs par nom
 * - Copie champ simple : update si valeur cible existante, création si absente, skip si source null
 * - Copie champ parent non-tableau : matching enfants par nom
 * - Copie champ parent tableau : suppression existants + création nouveaux avec idx
 * - Copie de tous les champs de valeur (str, longStr, number, checkbox, date)
 * - Gestion des cas limites (rubrique absente, champ absent)
 * - Logs warning pour les éléments non trouvés dans la cible
 */
final class DescriptifCopierTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private DescriptifCopier $service;

    protected function setUp(): void
    {
        $this->recreateService($this->createStub(EntityManagerInterface::class), $this->createStub(LoggerInterface::class));
    }

    /**
     * @return MockObject&EntityManagerInterface
     */
    private function useEntityManagerMock(): MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->recreateService($entityManager, $this->logger);

        return $entityManager;
    }

    /**
     * @return MockObject&LoggerInterface
     */
    private function useLoggerMock(): MockObject
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->recreateService($this->entityManager, $logger);

        return $logger;
    }

    private function recreateService(EntityManagerInterface $entityManager, LoggerInterface $logger): void
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->service = new DescriptifCopier($this->entityManager, $this->logger);
    }

    // -------------------------------------------------------------------------
    // matchRubriqueByNom
    // -------------------------------------------------------------------------

    public function testMatchRubriqueByNomRetourneItemCorrespondant(): void
    {
        $rubrique = $this->buildRubrique(1, 'Accès');
        $rubriques = [['rubrique' => $rubrique, 'display' => true, 'champs' => []]];

        $result = $this->service->matchRubriqueByNom($rubriques, 'Accès');

        $this->assertSame($rubrique, $result['rubrique']);
    }

    public function testMatchRubriqueByNomRetourneNullSiAbsent(): void
    {
        $rubriques = [['rubrique' => $this->buildRubrique(1, 'Accès'), 'display' => true, 'champs' => []]];

        $this->assertNull($this->service->matchRubriqueByNom($rubriques, 'Inconnu'));
    }

    // -------------------------------------------------------------------------
    // matchChampByNom
    // -------------------------------------------------------------------------

    public function testMatchChampByNomRetourneDataCorrespondant(): void
    {
        $champ = $this->buildSimpleChamp(10, 'Largeur');
        $champs = [['champ' => $champ, 'valeur' => null]];

        $result = $this->service->matchChampByNom($champs, 'Largeur');

        $this->assertSame($champ, $result['champ']);
    }

    public function testMatchChampByNomRetourneNullSiAbsent(): void
    {
        $champs = [['champ' => $this->buildSimpleChamp(10, 'Largeur'), 'valeur' => null]];

        $this->assertNull($this->service->matchChampByNom($champs, 'Inconnu'));
    }

    // -------------------------------------------------------------------------
    // Copie simple : source sans valeur → skip
    // -------------------------------------------------------------------------

    public function testCopieSimpleSkipSiSourceNull(): void
    {
        $this->useEntityManagerMock()->expects($this->never())->method('persist');

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $targetChamp = $this->buildSimpleChamp(20, 'Largeur');

        $sourceRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(10, 'Largeur'), 'valeur' => null],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $targetChamp, 'valeur' => null],
            ]),
        ];

        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(0, $etablissement->getValeurs());
    }

    // -------------------------------------------------------------------------
    // Copie simple : target a déjà une valeur → update
    // -------------------------------------------------------------------------

    public function testCopieSimpleUpdateValeurExistante(): void
    {
        $this->useEntityManagerMock()->expects($this->never())->method('persist');

        $sourceValeur = new Valeur()->setStr('texte source');
        $targetValeur = new Valeur()->setStr('ancien texte');
        $targetChamp = $this->buildSimpleChamp(20, 'Largeur');

        $sourceRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(10, 'Largeur'), 'valeur' => $sourceValeur],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $targetChamp, 'valeur' => $targetValeur],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([$targetValeur]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertSame('texte source', $targetValeur->getStr());
    }

    // -------------------------------------------------------------------------
    // Copie simple : target sans valeur → création
    // -------------------------------------------------------------------------

    public function testCopieSimpleCreationSiTargetNull(): void
    {
        $this->useEntityManagerMock()->expects($this->once())->method('persist')
            ->with(self::isInstanceOf(Valeur::class));

        $sourceValeur = new Valeur()->setStr('texte source');
        $targetChamp = $this->buildSimpleChamp(20, 'Largeur');

        $sourceRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(10, 'Largeur'), 'valeur' => $sourceValeur],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $targetChamp, 'valeur' => null],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(1, $etablissement->getValeurs());
        $newValeur = $etablissement->getValeurs()->first();
        $this->assertSame($targetChamp, $newValeur->getChamp());
        $this->assertSame('texte source', $newValeur->getStr());
    }

    // -------------------------------------------------------------------------
    // applySourceToTarget : copie tous les champs de valeur
    // -------------------------------------------------------------------------

    public function testApplySourceCopieTousLesChamps(): void
    {
        $date = new \DateTimeImmutable('2025-01-15');
        $sourceValeur = new Valeur()
            ->setStr('texte')
            ->setLongStr('long texte')
            ->setNumber(42)
            ->setCheckbox(true)
            ->setDate($date);
        $targetValeur = new Valeur();
        $targetChamp = $this->buildSimpleChamp(20, 'Données');

        $sourceRubriques = [
            $this->buildRubriqueItem('Info', [
                ['champ' => $this->buildSimpleChamp(10, 'Données'), 'valeur' => $sourceValeur],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Info', [
                ['champ' => $targetChamp, 'valeur' => $targetValeur],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([$targetValeur]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertSame('texte', $targetValeur->getStr());
        $this->assertSame('long texte', $targetValeur->getLongStr());
        $this->assertSame(42, $targetValeur->getNumber());
        $this->assertTrue($targetValeur->getCheckbox());
        $this->assertSame($date, $targetValeur->getDate());
    }

    // -------------------------------------------------------------------------
    // Rubrique absente dans la cible → ignorée + log
    // -------------------------------------------------------------------------

    public function testRubriqueAbsenteDansCibleEstIgnoree(): void
    {
        $this->useEntityManagerMock()->expects($this->never())->method('persist');

        $sourceRubriques = [
            $this->buildRubriqueItem('Rubrique Absente', [
                ['champ' => $this->buildSimpleChamp(10, 'Champ'), 'valeur' => new Valeur()->setStr('v')],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Autre Rubrique', [
                ['champ' => $this->buildSimpleChamp(20, 'Champ'), 'valeur' => null],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(0, $etablissement->getValeurs());
    }

    public function testRubriqueAbsenteDansCibleLoggue(): void
    {
        $this->useLoggerMock()->expects($this->once())->method('warning')
            ->with(self::stringContains('rubrique'));

        $sourceRubriques = [
            $this->buildRubriqueItem('Rubrique Absente', [
                ['champ' => $this->buildSimpleChamp(10, 'Champ'), 'valeur' => new Valeur()->setStr('v')],
            ]),
        ];

        $this->service->copyFromDossierToEtablissement($sourceRubriques, [], new Etablissement());
    }

    // -------------------------------------------------------------------------
    // Champ absent dans la cible → ignoré + log
    // -------------------------------------------------------------------------

    public function testChampAbsentDansCibleEstIgnore(): void
    {
        $this->useEntityManagerMock()->expects($this->never())->method('persist');

        $sourceRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(10, 'Champ Absent'), 'valeur' => new Valeur()->setStr('v')],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(20, 'Autre Champ'), 'valeur' => null],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(0, $etablissement->getValeurs());
    }

    public function testChampAbsentDansCibleLoggue(): void
    {
        $this->useLoggerMock()->expects($this->once())->method('warning')
            ->with(self::stringContains('champ'));

        $sourceRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(10, 'Champ Absent'), 'valeur' => new Valeur()->setStr('v')],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Accès', [
                ['champ' => $this->buildSimpleChamp(20, 'Autre Champ'), 'valeur' => null],
            ]),
        ];

        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, new Etablissement());
    }

    // -------------------------------------------------------------------------
    // Parent non-tableau : copie des sous-champs par nom
    // -------------------------------------------------------------------------

    public function testCopieParentNonTableauCopieEnfantsParNom(): void
    {
        $this->useEntityManagerMock()->expects($this->once())->method('persist');

        $sourceValeur = new Valeur()->setStr('valeur enfant');
        $targetChampEnfant = $this->buildSimpleChamp(21, 'Sous-champ A');

        $sourceParent = $this->buildParentChamp(10, 'ParentChamp', false);
        $sourceEnfant = $this->buildSimpleChamp(11, 'Sous-champ A');

        $targetParent = $this->buildParentChamp(20, 'ParentChamp', false);
        $targetParent->addEnfant($targetChampEnfant);

        $sourceRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $sourceParent,
                    'valeur' => null,
                    'sous_champs' => [
                        ['champ' => $sourceEnfant, 'valeur' => $sourceValeur],
                    ],
                ],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $targetParent,
                    'valeur' => null,
                    'sous_champs' => [
                        ['champ' => $targetChampEnfant, 'valeur' => null],
                    ],
                ],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(1, $etablissement->getValeurs());
        $newValeur = $etablissement->getValeurs()->first();
        $this->assertSame($targetChampEnfant, $newValeur->getChamp());
        $this->assertSame('valeur enfant', $newValeur->getStr());
    }

    public function testCopieParentNonTableauEnfantAbsentDansCibleIgnore(): void
    {
        $this->useEntityManagerMock()->expects($this->never())->method('persist');

        $sourceParent = $this->buildParentChamp(10, 'ParentChamp', false);
        $sourceEnfant = $this->buildSimpleChamp(11, 'Sous-champ Absent');

        $targetParent = $this->buildParentChamp(20, 'ParentChamp', false);
        $targetEnfant = $this->buildSimpleChamp(21, 'Autre Sous-champ');
        $targetParent->addEnfant($targetEnfant);

        $sourceRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $sourceParent,
                    'valeur' => null,
                    'sous_champs' => [
                        ['champ' => $sourceEnfant, 'valeur' => new Valeur()->setStr('v')],
                    ],
                ],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $targetParent,
                    'valeur' => null,
                    'sous_champs' => [
                        ['champ' => $targetEnfant, 'valeur' => null],
                    ],
                ],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(0, $etablissement->getValeurs());
    }

    // -------------------------------------------------------------------------
    // Parent tableau : suppression existants + création nouveaux
    // -------------------------------------------------------------------------

    public function testCopieParentTableauSupprimeLignesExistantes(): void
    {
        $valeurExistante = new Valeur();
        $this->useEntityManagerMock()->expects($this->once())->method('remove')->with($valeurExistante);

        $sourceParent = $this->buildParentChamp(10, 'Tableau', true);
        $targetParent = $this->buildParentChamp(20, 'Tableau', true);
        $targetEnfant = $this->buildSimpleChamp(21, 'Col A');
        $targetParent->addEnfant($targetEnfant);

        $sourceRubriques = [
            $this->buildRubriqueItem('Info', [
                ['champ' => $sourceParent, 'valeur' => null, 'lignes' => []],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $targetParent,
                    'valeur' => null,
                    'lignes' => [[21 => $valeurExistante]],
                ],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([$valeurExistante]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(0, $etablissement->getValeurs());
    }

    public function testCopieParentTableauCreeLignesSourceAvecIdxCorrect(): void
    {
        $this->useEntityManagerMock()->expects($this->exactly(2))->method('persist');

        $sourceParent = $this->buildParentChamp(10, 'Tableau', true);
        $sourceEnfantA = $this->buildSimpleChamp(11, 'Col A');
        $sourceParent->addEnfant($sourceEnfantA);

        $targetParent = $this->buildParentChamp(20, 'Tableau', true);
        $targetEnfantA = $this->buildSimpleChamp(21, 'Col A');
        $targetParent->addEnfant($targetEnfantA);

        $valeurLigne1 = new Valeur()->setStr('ligne 1')->setIdx(1);
        $valeurLigne2 = new Valeur()->setStr('ligne 2')->setIdx(2);
        $this->setEntityId($sourceEnfantA, 11);

        $sourceRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $sourceParent,
                    'valeur' => null,
                    'lignes' => [
                        [11 => $valeurLigne1],
                        [11 => $valeurLigne2],
                    ],
                ],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Info', [
                ['champ' => $targetParent, 'valeur' => null, 'lignes' => []],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(2, $etablissement->getValeurs());
        $valeurs = $etablissement->getValeurs()->toArray();
        $this->assertSame('ligne 1', $valeurs[0]->getStr());
        $this->assertSame(1, $valeurs[0]->getIdx());
        $this->assertSame('ligne 2', $valeurs[1]->getStr());
        $this->assertSame(2, $valeurs[1]->getIdx());
        $this->assertSame($targetEnfantA, $valeurs[0]->getChamp());
    }

    public function testCopieParentTableauColonneAbsenteDansCibleIgnoree(): void
    {
        $this->useEntityManagerMock()->expects($this->never())->method('persist');

        $sourceParent = $this->buildParentChamp(10, 'Tableau', true);
        $sourceEnfant = $this->buildSimpleChamp(11, 'Col Absente');
        $sourceParent->addEnfant($sourceEnfant);
        $this->setEntityId($sourceEnfant, 11);

        $targetParent = $this->buildParentChamp(20, 'Tableau', true);
        $targetEnfant = $this->buildSimpleChamp(21, 'Autre Col');
        $targetParent->addEnfant($targetEnfant);

        $sourceRubriques = [
            $this->buildRubriqueItem('Info', [
                [
                    'champ' => $sourceParent,
                    'valeur' => null,
                    'lignes' => [[11 => new Valeur()->setStr('val')]],
                ],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Info', [
                ['champ' => $targetParent, 'valeur' => null, 'lignes' => []],
            ]),
        ];

        $etablissement = $this->buildEtablissementAvecValeurs([]);
        $this->service->copyFromDossierToEtablissement($sourceRubriques, $targetRubriques, $etablissement);

        $this->assertCount(0, $etablissement->getValeurs());
    }

    // -------------------------------------------------------------------------
    // Direction inverse : établissement → dossier
    // -------------------------------------------------------------------------

    public function testCopieEtablissementVersDossier(): void
    {
        $this->useEntityManagerMock()->expects($this->once())->method('persist');

        $sourceValeur = new Valeur()->setStr('valeur établissement');
        $targetChamp = $this->buildSimpleChamp(20, 'Champ A');

        $sourceRubriques = [
            $this->buildRubriqueItem('Rubrique', [
                ['champ' => $this->buildSimpleChamp(10, 'Champ A'), 'valeur' => $sourceValeur],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Rubrique', [
                ['champ' => $targetChamp, 'valeur' => null],
            ]),
        ];

        $dossier = $this->buildDossierAvecValeurs([]);
        $this->service->copyFromEtablissementToDossier($sourceRubriques, $targetRubriques, $dossier);

        $this->assertCount(1, $dossier->getValeurs());
        $this->assertSame('valeur établissement', $dossier->getValeurs()->first()->getStr());
    }

    // -------------------------------------------------------------------------
    // copyFromDossierToDossier
    // -------------------------------------------------------------------------

    public function testCopieDossierVersDossier(): void
    {
        $this->useEntityManagerMock()->expects($this->once())->method('persist');

        $sourceValeur = new Valeur()->setStr('valeur dossier source');
        $targetChamp = $this->buildSimpleChamp(30, 'Champ A');

        $sourceRubriques = [
            $this->buildRubriqueItem('Rubrique', [
                ['champ' => $this->buildSimpleChamp(10, 'Champ A'), 'valeur' => $sourceValeur],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Rubrique', [
                ['champ' => $targetChamp, 'valeur' => null],
            ]),
        ];

        $dossierCible = $this->buildDossierAvecValeurs([]);
        $this->service->copyFromDossierToDossier($sourceRubriques, $targetRubriques, $dossierCible);

        $this->assertCount(1, $dossierCible->getValeurs());
        $this->assertSame('valeur dossier source', $dossierCible->getValeurs()->first()->getStr());
    }

    public function testCopieDossierVersDossierNeModifiePasDossierSource(): void
    {
        $this->useEntityManagerMock()->expects($this->once())->method('persist');

        $sourceValeur = new Valeur()->setStr('valeur originale');
        $targetChamp = $this->buildSimpleChamp(31, 'Champ A');

        $sourceRubriques = [
            $this->buildRubriqueItem('Rubrique', [
                ['champ' => $this->buildSimpleChamp(11, 'Champ A'), 'valeur' => $sourceValeur],
            ]),
        ];
        $targetRubriques = [
            $this->buildRubriqueItem('Rubrique', [
                ['champ' => $targetChamp, 'valeur' => null],
            ]),
        ];

        $dossierSource = $this->buildDossierAvecValeurs([$sourceValeur]);
        $dossierCible = $this->buildDossierAvecValeurs([]);
        $this->service->copyFromDossierToDossier($sourceRubriques, $targetRubriques, $dossierCible);

        $this->assertCount(1, $dossierSource->getValeurs(), 'Le dossier source ne doit pas être modifié.');
        $this->assertSame('valeur originale', $sourceValeur->getStr(), 'La valeur source ne doit pas être modifiée.');
    }

    // -------------------------------------------------------------------------
    // Flush appelé à la fin
    // -------------------------------------------------------------------------

    public function testFlushEstAppelee(): void
    {
        $this->useEntityManagerMock()->expects($this->once())->method('flush');

        $this->service->copyFromDossierToEtablissement(
            [],
            [],
            new Etablissement()
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function setEntityId(Champ|Rubrique|TypeChamp $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity::class, 'id');
        $ref->setValue($entity, $id);
    }

    private function buildRubrique(int $id, string $nom): Rubrique
    {
        $rubrique = new Rubrique();
        $this->setEntityId($rubrique, $id);
        $rubrique->setNom($nom);

        return $rubrique;
    }

    private function buildSimpleChamp(int $id, string $nom): Champ
    {
        $champ = new Champ();
        $this->setEntityId($champ, $id);
        $champ->setNom($nom);
        $type = new TypeChamp();
        $this->setEntityId($type, TypeChampConstant::TEXT);
        $champ->setType($type);

        return $champ;
    }

    private function buildParentChamp(int $id, string $nom, bool $tableau): Champ
    {
        $champ = new Champ();
        $this->setEntityId($champ, $id);
        $champ->setNom($nom);
        $champ->setTableau($tableau);

        $type = new TypeChamp();
        $this->setEntityId($type, TypeChampConstant::PARENT);
        $champ->setType($type);

        return $champ;
    }

    /**
     * @param list<array<string, mixed>> $champs
     *
     * @return array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}
     */
    private function buildRubriqueItem(string $nom, array $champs): array
    {
        static $id = 100;
        $rubrique = $this->buildRubrique($id++, $nom);

        return ['rubrique' => $rubrique, 'display' => true, 'champs' => $champs];
    }

    /**
     * @param Valeur[] $valeurs
     */
    private function buildEtablissementAvecValeurs(array $valeurs): Etablissement
    {
        $etablissement = new Etablissement();
        foreach ($valeurs as $valeur) {
            $etablissement->getValeurs()->add($valeur);
        }

        return $etablissement;
    }

    /**
     * @param Valeur[] $valeurs
     */
    private function buildDossierAvecValeurs(array $valeurs): Dossier
    {
        $dossier = new Dossier();
        foreach ($valeurs as $valeur) {
            $dossier->getValeurs()->add($valeur);
        }

        return $dossier;
    }
}
