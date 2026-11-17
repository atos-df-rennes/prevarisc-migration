<?php

declare(strict_types=1);

namespace App\Tests\Dto\Platau;

use App\Dto\Platau\VersionInfo;
use PHPUnit\Framework\TestCase;

final class VersionInfoTest extends TestCase
{
    public function testFromApiResponseExtractsFields(): void
    {
        $response = new \stdClass();
        $response->version = '1.2.3';
        $response->versionModel = 'M-5';
        $response->versionNomenclature = 'N-8';

        $info = VersionInfo::fromApiResponse($response);

        $this->assertSame('1.2.3', $info->getVersion());
        $this->assertSame('M-5', $info->getVersionModel());
        $this->assertSame('N-8', $info->getVersionNomenclature());
    }

    public function testFromApiResponseNormalizesEmptyStringsToNull(): void
    {
        $response = new \stdClass();
        $response->version = '';
        $response->versionModel = '   ';
        $response->versionNomenclature = null;

        $info = VersionInfo::fromApiResponse($response);

        $this->assertNull($info->getVersion());
        $this->assertNull($info->getVersionModel());
        $this->assertNull($info->getVersionNomenclature());
    }

    public function testFromApiResponseHandlesMissingFields(): void
    {
        $info = VersionInfo::fromApiResponse(new \stdClass());

        $this->assertNull($info->getVersion());
        $this->assertNull($info->getVersionModel());
        $this->assertNull($info->getVersionNomenclature());
    }

    public function testJsonSerialize(): void
    {
        $info = new VersionInfo('1.0', 'model', 'nomen');

        $json = json_encode($info);
        $data = json_decode((string) $json, true);

        $this->assertSame('1.0', $data['version']);
        $this->assertSame('model', $data['versionModel']);
        $this->assertSame('nomen', $data['versionNomenclature']);
    }
}
