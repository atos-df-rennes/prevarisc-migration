<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Utilisateur;
use App\Service\UtilisateurManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UtilisateurManagerTest extends TestCase
{
    #[DataProvider('providePasswordCases')]
    public function testProcessPasswordBeforePersist(?string $initialPassword, bool $sso, ?string $plainPassword, ?string $expectedDesc): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $user = new Utilisateur();
        $user->setUsername('jsmith');
        $user->setPassword($initialPassword);
        $user->setSso($sso);

        if (null !== $plainPassword) {
            $user->setPlainPassword($plainPassword);
        }

        if (null !== $expectedDesc && str_starts_with($expectedDesc, 'encoded:')) {
            // The concrete hash produced by the "auto" algorithm is salted/versioned and
            // therefore not reproducible in a test: we only assert that the manager
            // delegates hashing to the hasher and persists whatever it returns.
            $raw = substr($expectedDesc, strlen('encoded:'));
            $hashed = 'hashed:'.$raw;
            $hasher->expects($this->once())
                ->method('hashPassword')
                ->with($user, $raw)
                ->willReturn($hashed);
        } else {
            $hasher->expects($this->never())->method('hashPassword');
        }

        $service = new UtilisateurManager($hasher);
        $service->processPasswordBeforePersist($user);

        if (null === $expectedDesc) {
            $this->assertNull($user->getPassword());

            return;
        }

        if ('keep' === $expectedDesc) {
            $this->assertSame($initialPassword, $user->getPassword());

            return;
        }

        if (str_starts_with($expectedDesc, 'encoded:')) {
            $raw = substr($expectedDesc, strlen('encoded:'));
            $this->assertSame('hashed:'.$raw, $user->getPassword());

            return;
        }

        $this->fail('Unexpected expectedDesc: '.$expectedDesc);
    }

    public static function providePasswordCases(): \Iterator
    {
        // initialPassword, sso, plainPassword, expectedPasswordDescription
        // expectedPasswordDescription: 'encoded:<raw>' means the hasher must be called
        // with <raw> and its return value persisted,
        // null means password should be null,
        // 'keep' means initialPassword must be kept.
        yield 'create sso no plain' => [null, true, null, null];
        yield 'create non-sso no plain' => [null, false, null, 'keep'];
        yield 'create non-sso empty plain' => [null, false, '', 'keep'];
        yield 'create non-sso with plain' => [null, false, 'secret', 'encoded:secret'];
        yield 'update non-sso keep existing' => ['oldhash', false, null, 'keep'];
        yield 'update to sso clears password' => ['oldhash', true, null, null];
        yield 'update non-sso replace with empty plain keeps old' => ['oldhash', false, '', 'keep'];
        yield 'update non-sso replace with new plain' => ['oldhash', false, 'newpw', 'encoded:newpw'];
    }
}
