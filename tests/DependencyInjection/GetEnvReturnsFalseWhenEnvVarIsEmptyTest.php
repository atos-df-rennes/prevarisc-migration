<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use PHPUnit\Framework\TestCase;

class GetEnvReturnsFalseWhenEnvVarIsEmptyTest extends TestCase
{
    public function testGetEnvReturnsFalseWhenEnvVarIsEmpty(): void
    {
        $processor = new ThirdpartyPluginEnvVarProcessor();

        $result = $processor->getEnv(
            'sdis29_plugin_enabled',
            'PREVARISC_THIRDPARTY_PLUGINS',
            static function () {
                return '';
            }
        );

        $this->assertFalse($result);
    }
}
