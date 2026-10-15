<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SupprimerUtilisateursGroupeCommand;
use App\Enum\ResultatSuppressionUtilisateurEnum;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use App\Service\SuppressionUtilisateurs\SuppressionUtilisateursService;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SupprimerUtilisateursGroupeCommandTest extends TestCase
{
    private UtilisateurRepository&MockObject $utilisateurRepository;

    private TestHandler $journal;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $groupeRepository = $this->createStub(GroupeRepository::class);
        $groupeRepository->method('findAvecNombreUtilisateurs')->willReturn([
            ['id' => 1, 'libelle' => 'Groupe par défaut', 'nombreUtilisateurs' => 3],
            ['id' => 7, 'libelle' => 'Maires', 'nombreUtilisateurs' => 2],
        ]);

        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->utilisateurRepository->method('findIdsByGroupe')->willReturn([10, 11]);
        $this->utilisateurRepository->method('findReferencesBloquantes')->willReturn([]);

        $this->journal = new TestHandler();
        $service = new SuppressionUtilisateursService($groupeRepository, $this->utilisateurRepository, new Logger('test', [$this->journal]));

        $application = new Application();
        $application->addCommand(new SupprimerUtilisateursGroupeCommand($service));

        $this->tester = new CommandTester($application->find('app:utilisateurs:supprimer-groupe'));
    }

    public function testRefuseLExecutionNonInteractive(): void
    {
        $this->utilisateurRepository->expects($this->never())->method('supprimerDefinitivement');

        $code = $this->tester->execute([], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertTrue($this->journal->hasWarningThatContains('Exécution non interactive refusée'));
    }

    public function testLeGroupeSaisiParSonIdentifiantEstProtege(): void
    {
        $this->utilisateurRepository->expects($this->never())->method('supprimerDefinitivement');
        $this->tester->setInputs(['1']);

        $this->assertSame(Command::FAILURE, $this->tester->execute([]));
        $this->assertStringContainsString('est protégé', $this->tester->getDisplay());
    }

    public function testLAnnulationNeSupprimeRien(): void
    {
        $this->utilisateurRepository->expects($this->never())->method('supprimerDefinitivement');
        $this->tester->setInputs(['7', 'no']);

        $this->assertSame(Command::SUCCESS, $this->tester->execute([]));
        $this->assertTrue($this->journal->hasInfoThatContains("Suppression annulée par l'opérateur"));
    }

    public function testLaSimulationNeDemandePasDeConfirmation(): void
    {
        $this->utilisateurRepository->expects($this->never())->method('supprimerDefinitivement');
        $this->tester->setInputs(['7']);

        $this->assertSame(Command::SUCCESS, $this->tester->execute(['--dry-run' => true]));
        $this->assertStringContainsString('Simulation terminée', $this->tester->getDisplay());
    }

    public function testEchoueSiUneSuppressionEstEnErreur(): void
    {
        $this->utilisateurRepository->expects($this->exactly(2))->method('supprimerDefinitivement')
            ->willReturnCallback(static fn (int $id): ResultatSuppressionUtilisateurEnum => 10 === $id
                ? ResultatSuppressionUtilisateurEnum::Supprime
                : throw new \RuntimeException('Erreur SQL'));
        $this->tester->setInputs(['7', 'yes']);

        $this->assertSame(Command::FAILURE, $this->tester->execute([]));
        $this->assertStringContainsString('Erreur SQL', $this->tester->getDisplay());
    }
}
