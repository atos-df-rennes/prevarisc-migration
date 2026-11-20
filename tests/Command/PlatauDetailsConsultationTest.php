<?php

declare(strict_types=1);

namespace App\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[WithEnvironmentVariable('PISTE_CLIENT_ID', 'client-recette')]
#[WithEnvironmentVariable('PISTE_CLIENT_SECRET', 'secret-fictif')]
#[WithEnvironmentVariable('PLATAU_ID_ACTEUR_APPELANT', 'ACTEUR-TEST')]
#[WithEnvironmentVariable('PISTE_ACCESS_TOKEN_URL', 'https://oauth.example.test/token')]
#[WithEnvironmentVariable('PLATAU_URL', 'https://api.example.test/v13/dau/')]
#[WithEnvironmentVariable('PREVARISC_PROXY_ENABLED', '1')]
#[WithEnvironmentVariable('PREVARISC_PROXY_HOST', 'proxy.example.test')]
#[WithEnvironmentVariable('PREVARISC_PROXY_PORT', '8080')]
#[WithEnvironmentVariable('PREVARISC_PROXY_PROTOCOL', '0')]
#[WithEnvironmentVariable('PREVARISC_PROXY_USERNAME', '')]
final class PlatauDetailsConsultationTest extends KernelTestCase
{
    private const array CONSULTATION = [
        'dossier' => [
            'consultations' => [[
                'idConsultation' => 'AAA-BBB-CCC',
                'noVersion' => 2,
                'dtLimiteDeReponse' => '2026-03-19',
            ]],
        ],
    ];

    /**
     * @return iterable<string, array{string, ?string, string}>
     */
    public static function affichages(): iterable
    {
        yield 'sortie complète' => ['platau:details-consultation', null, "dossier.consultations.0.idConsultation : AAA-BBB-CCC\ndossier.consultations.0.noVersion : 2\ndossier.consultations.0.dtLimiteDeReponse : 2026-03-19"];
        yield 'alias et indice de liste' => ['details-consultation', 'dossier.consultations.0.noVersion', 'dossier.consultations.0.noVersion : 2'];
        yield 'champ absent' => ['platau:details-consultation', 'dossier.champAbsent', 'dossier.champAbsent : Aucune donnée'];
    }

    #[DataProvider('affichages')]
    public function testLitLaConsultationParIdentifiant(string $command, ?string $champ, string $expected): void
    {
        $tester = $this->application(new JsonMockResponse(['nombrePages' => 1, 'resultats' => [self::CONSULTATION]]));
        $arguments = ['command' => $command, 'consultation-id' => 'AAA-BBB-CCC'];
        if (null !== $champ) {
            $arguments['--champ'] = $champ;
        }

        $this->assertSame(Command::SUCCESS, $tester->run($arguments), $tester->getDisplay());
        $this->assertSame("Récupération de la consultation concernée ...\n".$expected."\n", $tester->getDisplay(true));
    }

    public function testSignaleUneConsultationIntrouvable(): void
    {
        $tester = $this->application(new JsonMockResponse(['nombrePages' => 0, 'resultats' => []]));

        $this->assertNotSame(Command::SUCCESS, $tester->run(['command' => 'details-consultation', 'consultation-id' => 'AAA-BBB-CCC']));
        $this->assertStringContainsString('AAA-BBB-CCC', $tester->getDisplay());
        $this->assertStringContainsString('introuvable', $tester->getDisplay());
    }

    public function testDistingueUnRefusHttpDuneRechercheVide(): void
    {
        $tester = $this->application(new JsonMockResponse(['message' => 'Acces refuse'], ['http_code' => 403]));

        $this->assertNotSame(Command::SUCCESS, $tester->run(['command' => 'platau:details-consultation', 'consultation-id' => 'AAA-BBB-CCC']));
        $this->assertSame("Récupération de la consultation concernée ...\nPlat'AU : accès refusé (HTTP 403).\n", $tester->getDisplay(true));
    }

    public function testExigeUnIdentifiantAvantToutAppelHttp(): void
    {
        $transport = new MockHttpClient([]);
        $tester = $this->console($transport);

        $this->assertNotSame(Command::SUCCESS, $tester->run(['command' => 'details-consultation']));
        $this->assertStringContainsString('consultation-id', $tester->getDisplay());
        $this->assertSame(0, $transport->getRequestsCount());
    }

    #[WithEnvironmentVariable('PLATAU_ID_ACTEUR_APPELANT', '')]
    public function testLaCommandeResteDisponibleSansConfigurationActeur(): void
    {
        $transport = new MockHttpClient([]);
        $tester = $this->console($transport);

        $this->assertSame(Command::SUCCESS, $tester->run(['command' => 'help', 'command_name' => 'details-consultation']));
        $this->assertNotSame(Command::SUCCESS, $tester->run(['command' => 'details-consultation', 'consultation-id' => 'AAA-BBB-CCC']));
        $this->assertStringContainsString('acteur', $tester->getDisplay());
        $this->assertSame(0, $transport->getRequestsCount());
    }

    private function application(MockResponse $page): ApplicationTester
    {
        return $this->console(new MockHttpClient(function (string $method, string $url, array $options) use ($page): MockResponse {
            $this->assertSame('POST', $method);
            $this->assertEqualsWithDelta(30.0, $options['timeout'], PHP_FLOAT_EPSILON);
            $this->assertSame('http://proxy.example.test:8080', $options['proxy']);
            if ('https://oauth.example.test/token' === $url) {
                parse_str($options['body'], $form);
                $this->assertSame(['grant_type' => 'client_credentials'], $form);
                $this->assertContains('Authorization: Basic '.base64_encode('client-recette:secret-fictif'), $options['headers']);
                $this->assertArrayNotHasKey('id-acteur-appelant', $options['normalized_headers']);

                return new JsonMockResponse(['access_token' => 'jeton-recette', 'expires_in' => 3600, 'token_type' => 'Bearer']);
            }

            $this->assertSame('https://api.example.test/v13/dau/consultations/recherche', explode('?', $url)[0]);
            parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);
            $this->assertSame('0', $query['numeroPage']);
            $this->assertSame('DT_LIMITE_DE_REPONSE', $query['colonneTri']);
            $this->assertSame('DESC', $query['sensTri']);
            $this->assertContains('Authorization: Bearer jeton-recette', $options['headers']);
            $this->assertContains('Id-Acteur-Appelant: ACTEUR-TEST', $options['headers']);
            $this->assertSame(['criteresSurConsultations' => ['idConsultation' => 'AAA-BBB-CCC']], json_decode($options['body'], true, flags: \JSON_THROW_ON_ERROR));

            return $page;
        }));
    }

    private function console(MockHttpClient $transport): ApplicationTester
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $container->set('cache.platau_consultation_tokens', new ArrayAdapter());
        $container->set('http_client.transport', $transport);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        return new ApplicationTester($application);
    }
}
