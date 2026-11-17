<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Constant\GenreEtablissement;
use App\Twig\AssetExistExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class AssetExistExtensionTest extends TestCase
{
    #[DataProvider('assetPathProvider')]
    public function testAssetExistChecksFilesRelativeToPublic(string $path, bool $expected): void
    {
        $extension = new AssetExistExtension(dirname(__DIR__, 2));

        $this->assertSame($expected, $extension->assetExist($path));
    }

    public static function assetPathProvider(): \Iterator
    {
        yield 'existing file' => ['images/types/b/icone-type-M.png', true];
        yield 'existing file with leading slash' => ['/images/types/b/icone-type-M.png', true];
        yield 'missing file' => ['images/types/b/icone-type-UNKNOWN.png', false];
        yield 'directory is not an asset' => ['images/types/b', false];
        yield 'URL is not a physical path' => ['images/types/b/icone-type-M.png?vtest', false];
        yield 'empty path' => ['', false];
    }

    #[DataProvider('templateProvider')]
    public function testTypeIconsUseVersionedUrlsAndFallbackForMissingFiles(string $template, string $basePath): void
    {
        $projectDir = dirname(__DIR__, 2);
        $extension = new AssetExistExtension($projectDir);
        $packages = new Packages(new PathPackage($basePath, new StaticVersionStrategy('vtest')));
        $twig = new Environment(new FilesystemLoader($projectDir.'/templates'));
        $twig->addFunction(new TwigFunction('asset_exist', $extension->assetExist(...)));
        $twig->addFunction(new TwigFunction('asset', $packages->getUrl(...)));
        $twig->addFunction(new TwigFunction('path', static fn (): string => '/etablissement/1'));

        $html = $twig->render($template, [
            'etablissement' => [
                'id' => 1,
                'latestInformations' => [
                    'type' => ['libelle' => 'M'],
                    'genre' => ['id' => GenreEtablissement::ERP, 'libelle' => 'ERP'],
                    'statut' => ['id' => 2],
                    'libelle' => 'Test',
                ],
                'filsActifs' => [],
            ],
            'infos' => [
                'genre' => ['id' => GenreEtablissement::ERP, 'libelle' => 'ERP'],
                'type' => ['libelle' => 'M'],
                'typeActivite' => ['libelle' => 'Principal'],
                'typesActivitesSecondaires' => [
                    ['type' => ['libelle' => 'N'], 'activite' => ['libelle' => 'Secondaire']],
                    ['type' => ['libelle' => 'UNKNOWN'], 'activite' => ['libelle' => 'Sans pictogramme']],
                ],
            ],
            'fils_ordonnes' => [],
        ]);

        $this->assertStringContainsString('src="'.$basePath.'/images/types/b/icone-type-M.png?vtest"', $html);
        $this->assertStringNotContainsString('?vtest?vtest', $html);

        if ('etablissement/_informations/_general.html.twig' === $template) {
            $this->assertStringContainsString('src="'.$basePath.'/images/types/b/icone-type-N.png?vtest"', $html);
            $this->assertStringContainsString('UNKNOWN&nbsp;-&nbsp;', $html);
            $this->assertStringContainsString('Sans pictogramme', $html);
            $this->assertStringNotContainsString('icone-type-UNKNOWN.png', $html);
        }
    }

    public static function templateProvider(): \Iterator
    {
        foreach (['', '/prevarisc'] as $basePath) {
            yield 'activities with base path '.$basePath => ['etablissement/_informations/_general.html.twig', $basePath];
            yield 'linked establishment with base path '.$basePath => ['etablissement/_informations/_etablissement_lies.html.twig', $basePath];
        }
    }
}
