<?php

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\ThirdpartyPluginEnvVarProcessor;
use PHPUnit\Framework\TestCase;

class GetEnvReturnsFalseWhenPluginSdis29AbsentTest extends TestCase
{
    public function testGetEnvReturnsFalseWhenPluginSdis29Absent(): void
    {
        $processor = new ThirdpartyPluginEnvVarProcessor();

        $result = $processor->getEnv(
            'sdis29_plugin_enabled',
            'PREVARISC_THIRDPARTY_PLUGINS',
            static function () {
                return 'Plugin_Foo;Plugin_Bar';
            }
        );

        $this->assertFalse($result);
    }
}
