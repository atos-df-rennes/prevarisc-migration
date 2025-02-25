<?php

namespace App\Tests\Service;

use App\Service\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    /**
     * @dataProvider envProvider
     */
    public function testFromEnv(string $branch, string $revision, array $expected): void
    {
        putenv('PREVARISC_BRANCH='.$branch);
        putenv('PREVARISC_REVISION='.$revision);

        $version = Version::create();

        $this->assertSame($expected['version'], $version->version());
        $this->assertSame($expected['link'], $version->link());
    }

    public function envProvider(): array
    {
        return [
            [
                '3.0',
                '0',
                [
                    'version' => '3.0.0',
                    'link' => 'https://github.com/atos-df-rennes/prevarisc/releases/tag/v3.0.0',
                ],
            ],
        ];
    }
}
