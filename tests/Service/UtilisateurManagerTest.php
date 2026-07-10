<?php

namespace App\Tests\Service;

use App\Entity\Utilisateur;
use App\Security\Md5SaltedEncoder;
use App\Service\UtilisateurManager;
use PHPUnit\Framework\TestCase;

final class UtilisateurManagerTest extends TestCase
{
    private string $globalSalt = 'globalsalt';

    public static function providePasswordCases(): array
    {
        // initialPassword, sso, plainPassword, expectedPasswordDescription
        // expectedPasswordDescription: 'encoded:<raw>' means encoded value expected,
        // null means password should be null,
        // 'keep' means initialPassword must be kept.
        return [
            'create sso no plain' => [null, true, null, null],
            'create non-sso no plain' => [null, false, null, 'keep'],
            'create non-sso empty plain' => [null, false, '', 'keep'],
            'create non-sso with plain' => [null, false, 'secret', 'encoded:secret'],
            'update non-sso keep existing' => ['oldhash', false, null, 'keep'],
            'update to sso clears password' => ['oldhash', true, null, null],
            'update non-sso replace with empty plain keeps old' => ['oldhash', false, '', 'keep'],
            'update non-sso replace with new plain' => ['oldhash', false, 'newpw', 'encoded:newpw'],
        ];
    }

    /**
     * @dataProvider providePasswordCases
     */
    public function testProcessPasswordBeforePersist(?string $initialPassword, bool $sso, ?string $plainPassword, ?string $expectedDesc): void
    {
        $encoder = new Md5SaltedEncoder($this->globalSalt);
        $service = new UtilisateurManager($encoder);

        $user = new Utilisateur();
        $user->setUsername('jsmith'); // username used as salt by encoder
        $user->setPassword($initialPassword);
        $user->setSso($sso);

        if (null !== $plainPassword) {
            $user->setPlainPassword($plainPassword);
        }

        $service->processPasswordBeforePersist($user);

        if (null === $expectedDesc) {
            self::assertNull($user->getPassword());

            return;
        }

        if ('keep' === $expectedDesc) {
            self::assertSame($initialPassword, $user->getPassword());

            return;
        }

        if (0 === strpos($expectedDesc, 'encoded:')) {
            $raw = substr($expectedDesc, strlen('encoded:'));
            $expected = md5($user->getSalt().$this->globalSalt.$raw);
            self::assertSame($expected, $user->getPassword());

            return;
        }

        $this->fail('Unexpected expectedDesc: '.$expectedDesc);
    }
}
