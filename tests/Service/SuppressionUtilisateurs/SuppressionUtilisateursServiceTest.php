<?php

declare(strict_types=1);

namespace App\Tests\Service\SuppressionUtilisateurs;

use App\Enum\MotifConservationUtilisateurEnum;
use App\Enum\ResultatSuppressionUtilisateurEnum;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use App\Service\SuppressionUtilisateurs\GroupeProtegeException;
use App\Service\SuppressionUtilisateurs\SuppressionUtilisateursService;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class SuppressionUtilisateursServiceTest extends TestCase
{
    private GroupeRepository&MockObject $groupeRepository;

    private UtilisateurRepository&MockObject $utilisateurRepository;

    private TestHandler $journal;

    private SuppressionUtilisateursService $service;

    protected function setUp(): void
    {
        $this->groupeRepository = $this->createMock(GroupeRepository::class);
        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->journal = new TestHandler();
        $this->service = new SuppressionUtilisateursService(
            $this->groupeRepository,
            $this->utilisateurRepository,
            new Logger('suppression_utilisateurs', [$this->journal]),
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function groupesProteges(): iterable
    {
        yield 'groupe par défaut' => [SuppressionUtilisateursService::GROUPE_PAR_DEFAUT_ID, false];
        yield "groupe avec droit d'administration" => [2, true];
    }

    #[DataProvider('groupesProteges')]
    public function testRefuseUnGroupeProtegeSansRienSupprimer(int $groupeId, bool $droitAdministration): void
    {
        $this->groupeRepository->method('possedePrivilege')->willReturn($droitAdministration);
        $this->utilisateurRepository->expects($this->never())->method('findIdsByGroupe');
        $this->utilisateurRepository->expects($this->never())->method('supprimerDefinitivement');

        try {
            $this->service->analyser($groupeId, 'Protégé', false);
            self::fail('Une GroupeProtegeException était attendue.');
        } catch (GroupeProtegeException) {
        }

        $this->assertTrue($this->journal->hasWarningThatContains('Groupe protégé'));
    }

    public function testSimulationNeSupprimeRienEtTraceLesUtilisateursConcernes(): void
    {
        $this->utilisateurRepository->method('findIdsByGroupe')->willReturn([10, 11, 12]);
        $this->utilisateurRepository->method('findReferencesBloquantes')
            ->willReturn([11 => [MotifConservationUtilisateurEnum::CreateurDossier]]);
        $this->utilisateurRepository->expects($this->never())->method('supprimerDefinitivement');

        $analyse = $this->service->analyser(7, 'Maires', true);
        $bilan = $this->service->executer($analyse);

        $this->assertTrue($bilan->simulation);
        $this->assertSame([10, 12], $bilan->supprimes);
        $this->assertSame([11], array_keys($bilan->conserves));
        $simulation = $this->enregistrement('Simulation : utilisateurs qui seraient supprimés');
        $this->assertSame([10, 12], $simulation['utilisateurs']);
        $this->assertSame(['createur_dossier'], $this->enregistrement('Utilisateur conservé : références métier')['motifs']);
    }

    public function testSuppressionIsoleLesErreursEtTraceChaqueIssue(): void
    {
        $this->utilisateurRepository->method('findIdsByGroupe')->willReturn([10, 11, 12, 13, 14]);
        $this->utilisateurRepository->method('findReferencesBloquantes')->willReturnOnConsecutiveCalls(
            [11 => [MotifConservationUtilisateurEnum::AuteurActualite]],
            // Référence apparue entre l'analyse et la confirmation.
            [14 => [MotifConservationUtilisateurEnum::PreventionnisteDossier]],
        );
        $this->utilisateurRepository->expects($this->exactly(3))->method('supprimerDefinitivement')
            ->willReturnCallback(static fn (int $id, int $groupeId): ResultatSuppressionUtilisateurEnum => match ($id) {
                10 => ResultatSuppressionUtilisateurEnum::SupprimeFicheConservee,
                12 => throw new \RuntimeException('Violation de contrainte'),
                13 => ResultatSuppressionUtilisateurEnum::Absent,
                default => self::fail('Utilisateur inattendu : '.$id),
            });

        $bilan = $this->service->executer($this->service->analyser(7, 'Maires', false));

        $this->assertSame([10], $bilan->supprimes);
        $this->assertSame([10], $bilan->fichesConservees);
        $this->assertSame([11, 14], array_keys($bilan->conserves));
        $this->assertSame([13], $bilan->absents);
        $this->assertSame([12 => 'Violation de contrainte'], $bilan->erreurs);
        $this->assertTrue($bilan->enErreur());

        $this->assertTrue($this->enregistrement('Utilisateur supprimé')['fiche_informations_partagee_conservee']);
        $this->assertSame(12, $this->enregistrement("Erreur lors de la suppression de l'utilisateur")['utilisateur_id']);
        $this->assertTrue($this->journal->hasWarningThatContains('Utilisateur absent du groupe'));
        $this->assertTrue($this->journal->hasErrorThatContains('Fin de la suppression'));
    }

    public function testLeJournalNeContientQueDesIdentifiantsTechniques(): void
    {
        $this->utilisateurRepository->method('findIdsByGroupe')->willReturn([10]);
        $this->utilisateurRepository->method('findReferencesBloquantes')->willReturn([]);
        $this->utilisateurRepository->method('supprimerDefinitivement')->willReturn(ResultatSuppressionUtilisateurEnum::Supprime);

        $analyse = $this->service->analyser(7, 'Maires', false);
        $this->service->executer($analyse);

        $records = $this->journal->getRecords();
        $this->assertNotEmpty($records);
        foreach ($records as $record) {
            $this->assertSame($analyse->executionId, $record->context['execution']);
            $this->assertSame(7, $record->context['groupe_id']);
            $this->assertEmpty(array_intersect(array_keys($record->context), ['username', 'nom', 'prenom', 'mail']));
        }

        $this->assertSame(Level::Info, array_last($records)->level);
    }

    /**
     * @return array<string, mixed>
     */
    private function enregistrement(string $message): array
    {
        foreach ($this->journal->getRecords() as $record) {
            if ($message === $record->message) {
                return $record->context;
            }
        }

        self::fail('Trace absente : '.$message);
    }
}
