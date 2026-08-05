<?php

namespace App\Tests\Service\Mail;

use App\Service\Mail\MailerConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MailerConfigurationTest extends TestCase
{
    /**
     * @param array{
     *     transport: string,
     *     host: string,
     *     port: string,
     *     username: string,
     *     password: string,
     *     sender: string,
     *     senderName: string,
     *     enabled: bool
     * } $parameters
     */
    #[DataProvider('parametersProvider')]
    public function testGetDsn(array $parameters, string $expected): void
    {
        $mailerConfiguration = new MailerConfiguration(...$parameters);

        $this->assertSame($expected, $mailerConfiguration->getDsn());
    }

    public static function parametersProvider(): iterable
    {
        yield 'mail disabled' => [
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                false
            ],
            'null://null',
        ];

        yield 'no known transport' => [
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                true
            ],
            'null://null',
        ];

        yield 'sendmail transport' => [
            [
                'sendmail',
                '',
                '',
                '',
                '',
                '',
                '',
                true
            ],
            'sendmail://default',
        ];

        yield 'mail transport' => [
            [
                'mail',
                '',
                '',
                '',
                '',
                '',
                '',
                true
            ],
            'sendmail://default',
        ];

        yield 'smtp transport with no optional info' => [
            [
                'smtp',
                'localhost',
                '',
                '',
                '',
                '',
                '',
                true
            ],
            'smtp://localhost',
        ];

        yield 'smtp transport with all info' => [
            [
                'smtp',
                'localhost',
                '25',
                'root',
                'root',
                '',
                '',
                true
            ],
            'smtp://root:root@localhost:25',
        ];
    }
}
