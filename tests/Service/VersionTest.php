<?php

namespace App\Tests\Service;

use App\Builder\VersionBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class VersionTest extends KernelTestCase
{
    /**
     * @dataProvider envProvider
     */
    public function testFromEnv(string $branch, string $revision, array $expected): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $versionBuilder = $container->get(VersionBuilder::class);

        putenv('PREVARISC_BRANCH='.$branch);
        putenv('PREVARISC_REVISION='.$revision);

        $version = $versionBuilder->build();

        $this->assertSame($expected['version'], $version->version());
        $this->assertSame($expected['link'], $version->link());
    }

    public static function envProvider(): array
    {
        return [
            [
                '3.0',
                '0',
                [
                    'version' => 'v3.0.0',
                    'link' => 'https://github.com/atos-df-rennes/prevarisc/releases/tag/v3.0.0',
                ],
            ],
        ];
    }
}
