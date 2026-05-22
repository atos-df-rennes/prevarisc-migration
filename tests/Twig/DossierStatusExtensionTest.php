<?php

namespace App\Tests\Twig;

use App\Constant\Avis;
use App\Constant\DossierType;
use App\Dto\Repository\DossierSearchResultDto;
use App\Service\DateDiffFormatterService;
use App\Twig\DossierStatusExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DossierStatusExtensionTest extends TestCase
{
    /** @var DateDiffFormatterService|MockObject */
    private $dateDiffFormatter;

    /** @var DossierStatusExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->dateDiffFormatter = $this->createMock(DateDiffFormatterService::class);
        $this->extension = new DossierStatusExtension($this->dateDiffFormatter);
    }

    // -------------------------------------------------------------------------
    // Branche 1 — Dossier incomplet
    // -------------------------------------------------------------------------

    public function testDossierIncompletRetourneOrangeBadge(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray(['INCOMPLET_DOSSIER' => 1]));

        $this->assertSame(
            '<span class="avis" style="background-color: #f89406;">Dossier incomplet</span>',
            $result
        );
    }

    public function testDossierIncompletEstPrioritaireSurDiffere(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'INCOMPLET_DOSSIER' => 1,
            'DIFFEREAVIS_DOSSIER' => 1,
        ]));

        $this->assertStringContainsString('Dossier incomplet', $result);
    }

    // -------------------------------------------------------------------------
    // Branche 2 — Avis différé
    // -------------------------------------------------------------------------

    public function testAvisDiffereRetourneBlueBadge(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray(['DIFFEREAVIS_DOSSIER' => 1]));

        $this->assertSame(
            '<span class="avis" style="background-color: #3a87ad;">Avis différé</span>',
            $result
        );
    }

    // -------------------------------------------------------------------------
    // Branche 3 — Courrier répondu
    // -------------------------------------------------------------------------

    public function testCourrierReponduRetourneRepondu(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'TYPE_DOSSIER' => DossierType::COURRIER,
            'DATEREP_DOSSIER' => '2023-01-15',
        ]));

        $this->assertSame('<span class="avis F">Répondu</span>', $result);
    }

    // -------------------------------------------------------------------------
    // Branche 4 — Courrier non répondu, sans DATESDIS_DOSSIER
    // -------------------------------------------------------------------------

    public function testCourrierNonReponduSansDateSdisRetourneReponseAFaireSansParenthese(): void
    {
        $this->dateDiffFormatter->expects($this->never())->method('format');

        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'TYPE_DOSSIER' => DossierType::COURRIER,
            'DATEREP_DOSSIER' => '',
            'DATESDIS_DOSSIER' => '',
        ]));

        $this->assertSame('<span class="avis">Réponse à faire</span>', $result);
    }

    // -------------------------------------------------------------------------
    // Branche 5 — Courrier non répondu, avec DATESDIS_DOSSIER renseigné
    // -------------------------------------------------------------------------

    public function testCourrierNonReponduAvecDateSdisRetourneReponseAFaireAvecDiff(): void
    {
        $this->dateDiffFormatter
            ->expects($this->once())
            ->method('format')
            ->with('2022-06-01 00:00:00')
            ->willReturn('3 mois, 5 jours');

        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'TYPE_DOSSIER' => DossierType::COURRIER,
            'DATEREP_DOSSIER' => '',
            'DATESDIS_DOSSIER' => '2022-06-01 00:00:00',
        ]));

        $this->assertSame('<span class="avis">Réponse à faire (3 mois, 5 jours)</span>', $result);
    }

    public function testCourrierAvecDateSdisRetourneReponseAFaireSansParentheseSiDiffVide(): void
    {
        $this->dateDiffFormatter
            ->expects($this->once())
            ->method('format')
            ->willReturn('');

        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'TYPE_DOSSIER' => DossierType::COURRIER,
            'DATEREP_DOSSIER' => '',
            'DATESDIS_DOSSIER' => '2022-06-01 00:00:00',
        ]));

        $this->assertSame('<span class="avis">Réponse à faire</span>', $result);
    }

    // -------------------------------------------------------------------------
    // Branche 6 — Favorable (sans / avec exploitation)
    // -------------------------------------------------------------------------

    public function testFavorableSansExploitationRetourneFavorable(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'ID_AVIS' => Avis::FAVORABLE,
            'ID_DOSSIERNATURE' => 1, // 1 n'est pas dans la liste exploitation
        ]));

        $this->assertSame('<span class="avis F">Favorable</span>', $result);
    }

    public function testFavorableAvecExploitationRetourneFavorableExploitation(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'ID_AVIS' => Avis::FAVORABLE,
            'ID_DOSSIERNATURE' => 7, // 7 est dans la liste exploitation
        ]));

        $this->assertSame('<span class="avis F">Favorable à l\'exploitation</span>', $result);
    }

    // -------------------------------------------------------------------------
    // Branche 7 — Défavorable (sans / avec exploitation)
    // -------------------------------------------------------------------------

    public function testDefavorableSansExploitationRetourneDefavorable(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'ID_AVIS' => Avis::DEFAVORABLE,
            'ID_DOSSIERNATURE' => 1, // hors liste exploitation
        ]));

        $this->assertSame('<span class="avis D">Défavorable</span>', $result);
    }

    public function testDefavorableAvecExploitationRetourneDefavorableExploitation(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([
            'ID_AVIS' => Avis::DEFAVORABLE,
            'ID_DOSSIERNATURE' => 16, // 16 est dans la liste exploitation
        ]));

        $this->assertSame('<span class="avis D">Défavorable à l\'exploitation</span>', $result);
    }

    // -------------------------------------------------------------------------
    // Branche 8 — Fallback (aucun cas reconnu)
    // -------------------------------------------------------------------------

    public function testFallbackRetourneAvisIndisponible(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray([]));

        $this->assertSame('<span class="avis">Avis indisponible</span>', $result);
    }

    public function testFallbackAvecAvisInconnuRetourneAvisIndisponible(): void
    {
        $result = $this->extension->renderAvisBadge(DossierSearchResultDto::fromArray(['ID_AVIS' => 99]));

        $this->assertSame('<span class="avis">Avis indisponible</span>', $result);
    }
}
