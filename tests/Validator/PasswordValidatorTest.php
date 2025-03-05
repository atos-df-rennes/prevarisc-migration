<?php

namespace App\Tests\Validator;

use App\Entity\Utilisateur;
use App\Validator\Password;
use App\Validator\PasswordValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PasswordValidator
    {
        return new PasswordValidator(true);
    }

    public function testWithSsoIsValid(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setSso(true);

        $this->validator->validate($utilisateur, new Password());

        $this->assertNoViolation();
    }

    public function testWithoutSsoAndValidPasswordIsValid(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setSso(false);
        $utilisateur->setPlainPassword('M3g4S3cur3P4ssw0rd.');

        $this->validator->validate($utilisateur, new Password());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testWithoutSsoAndWithoutPasswordIsInvalid(Password $constraint): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setSso(false);

        $this->validator->validate($utilisateur, $constraint);

        $this->buildViolation($constraint->vide)
            ->atPath('property.path.utilisateur.plainPassword')
            ->assertRaised()
        ;
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testWithoutSsoAndInvalidPasswordIsInvalid(Password $constraint): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setSso(false);
        $utilisateur->setPlainPassword('test');

        $this->validator->validate($utilisateur, $constraint);

        $this->buildViolation($constraint->securite)
            ->atPath('property.path.utilisateur.plainPassword')
            ->assertRaised()
        ;
    }

    public function provideInvalidConstraints(): iterable
    {
        yield [new Password()];
    }
}
