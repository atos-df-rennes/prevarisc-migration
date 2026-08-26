<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use PHPUnit\Framework\TestCase;

class GetEnvReturnsTrueWhenPluginSdis29PresentTest extends TestCase
{
    public function testGetEnvReturnsTrueWhenPluginSdis29Present(): void
    {
        $processor = new ThirdpartyPluginEnvVarProcessor();

        $result = $processor->getEnv(
            'sdis29_plugin_enabled',
            'PREVARISC_THIRDPARTY_PLUGINS',
            static function () {
                return 'Plugin_Foo;Plugin_SDIS29;Plugin_Bar';
            }
        );

        $this->assertTrue($result);
    }
}
