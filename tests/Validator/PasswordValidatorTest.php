<?php

namespace App\Tests\Validator;

use App\Entity\Utilisateur;
use App\Validator\Password;
use App\Validator\PasswordValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PasswordValidator
    {
        return new PasswordValidator(true);
    }

    #[DataProvider('passwordIsValid')]
    public function testIsValid(Utilisateur $utilisateur): void
    {
        $this->validator->validate($utilisateur, new Password());
        $this->assertNoViolation();
    }

    #[DataProvider('provideInvalidConstraints')]
    public function testWithoutSsoAndWithoutPasswordIsInvalidIfHasLogin(Password $constraint): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setUsername('test');
        $utilisateur->setSso(false);

        $this->validator->validate($utilisateur, $constraint);

        $this->buildViolation($constraint->vide)
            ->atPath('property.path.utilisateur.plainPassword')
            ->assertRaised()
        ;
    }

    #[DataProvider('provideInvalidConstraints')]
    public function testWithoutSsoAndInvalidPasswordIsInvalid(Password $constraint): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setUsername('test');
        $utilisateur->setSso(false);
        $utilisateur->setPlainPassword('test');

        $this->validator->validate($utilisateur, $constraint);

        $this->buildViolation($constraint->securite)
            ->atPath('property.path.utilisateur.plainPassword')
            ->assertRaised()
        ;
    }

    public static function passwordIsValid(): iterable
    {
        yield 'SSO' => [new Utilisateur()->setSso(true)];
        yield 'No SSO and valid password' => [new Utilisateur()->setSso(false)->setPlainPassword('M3g4S3cur3P4ssw0rd.')];
        yield 'No SSO, no password and no login' => [new Utilisateur()->setSso(false)];
    }

    public static function provideInvalidConstraints(): iterable
    {
        yield [new Password()];
    }
}
