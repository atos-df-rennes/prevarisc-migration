<?php

declare(strict_types=1);

namespace App\Tests\Service\RessourcesSpecialisees;

use App\Repository\DossierNatureListeRepository;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesBupTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesCampingTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesCelluleTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesErpTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesHabitationTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesIghTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesIopTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesManifestationTemporaireTexter;
use App\Service\RessourcesSpecialisees\Etablissement\Texter\RessourcesSpecialiseesZoneTexter;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierNameGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesDossierTextGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementNameGenerator;
use App\Service\RessourcesSpecialisees\RessourcesSpecialiseesEtablissementTextGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RessourcesSpecialiseesGeneratorsTest extends TestCase
{
    public function testEtablissementLitLesCriteresDansLeFormulaireSoumis(): void
    {
        $request = new Request([], [
            'ressource_specialisee_etablissement' => [
                'text' => '',
                'genre' => '2',
                'typesActivites' => ['3', '7'],
                'commissions' => '0',
                'groupements' => '0',
                'commune' => '1',
            ],
        ]);

        $erpTexter = $this->createMock(RessourcesSpecialiseesErpTexter::class);
        $erpTexter->expects($this->once())->method('generateText')->willReturn('Établissement (types 3 et 7)');
        $textGenerator = new RessourcesSpecialiseesEtablissementTextGenerator(
            $this->createStub(RessourcesSpecialiseesZoneTexter::class),
            $this->createStub(RessourcesSpecialiseesHabitationTexter::class),
            $this->createStub(RessourcesSpecialiseesIghTexter::class),
            $this->createStub(RessourcesSpecialiseesCelluleTexter::class),
            $erpTexter,
            $this->createStub(RessourcesSpecialiseesBupTexter::class),
            $this->createStub(RessourcesSpecialiseesCampingTexter::class),
            $this->createStub(RessourcesSpecialiseesIopTexter::class),
            $this->createStub(RessourcesSpecialiseesManifestationTemporaireTexter::class),
        );

        $this->assertSame('etablissement_erp_3-7_0_0_0_1', RessourcesSpecialiseesEtablissementNameGenerator::fromRequest($request));
        $this->assertSame('Établissement (types 3 et 7)', $textGenerator->fromRequest($request));
    }

    public function testDossierLitLesCriteresDansLeFormulaireSoumis(): void
    {
        $request = new Request([], [
            'ressource_specialisee_dossier' => [
                'text' => '',
            ],
        ]);
        $textGenerator = new RessourcesSpecialiseesDossierTextGenerator($this->createStub(DossierNatureListeRepository::class));

        $this->assertSame('dossier_0', RessourcesSpecialiseesDossierNameGenerator::fromRequest($request));
        $this->assertSame('Dossier (Toutes les natures)', $textGenerator->fromRequest($request));
    }
}
