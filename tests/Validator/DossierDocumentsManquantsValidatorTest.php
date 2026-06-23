<?php

namespace App\Tests\Validator;

use App\Entity\Dossier;
use App\Entity\DossierDocumentManquant;
use App\Validator\DossierDocumentsManquants;
use App\Validator\DossierDocumentsManquantsValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Tests unitaires pour le validator DossierDocumentsManquants.
 *
 * Ce validator vérifie que si un dossier est marqué incomplet :
 * - au moins un DossierDocumentManquant est présent dans la collection
 * - chaque document a les champs `date` et `documentManquant` renseignés
 * - `dateReception` reste nullable et n'est pas vérifiée
 */
final class DossierDocumentsManquantsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DossierDocumentsManquantsValidator
    {
        return new DossierDocumentsManquantsValidator();
    }

    /**
     * Un dossier non incomplet ne doit jamais lever de violation,
     * quelle que soit l'état de la collection.
     *
     * @dataProvider provideDossierNonIncomplet
     */
    public function testPasDErreurSiDossierNonIncomplet(?bool $incomplet): void
    {
        $dossier = new Dossier();
        $dossier->setIncomplet($incomplet);

        $this->validator->validate($dossier, new DossierDocumentsManquants());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{0: bool|null}>
     */
    public static function provideDossierNonIncomplet(): iterable
    {
        yield 'complet (false)' => [false];
        yield 'null' => [null];
    }

    /**
     * Un dossier incomplet sans aucun document manquant doit lever
     * une violation sur le chemin `documentsManquants`.
     */
    public function testViolationSiCollectionVide(): void
    {
        $dossier = new Dossier();
        $dossier->setIncomplet(true);

        $constraint = new DossierDocumentsManquants();
        $this->validator->validate($dossier, $constraint);

        $this->buildViolation($constraint->messageCollectionVide)
            ->atPath('property.path.documentsManquants')
            ->assertRaised()
        ;
    }

    /**
     * Un dossier incomplet avec un document dont la date est nulle
     * doit lever une violation ciblée sur documentsManquants[0].date.
     */
    public function testViolationSiDocumentSansDate(): void
    {
        $doc = new DossierDocumentManquant();
        $doc->setDocumentManquant('Liste des pièces manquantes');
        // date intentionnellement non renseignée

        $dossier = new Dossier();
        $dossier->setIncomplet(true);
        $dossier->addDocumentsManquant($doc);

        $constraint = new DossierDocumentsManquants();
        $this->validator->validate($dossier, $constraint);

        $this->buildViolation($constraint->messageChampsManquants)
            ->atPath('property.path.documentsManquants[0].date')
            ->assertRaised()
        ;
    }

    /**
     * Un dossier incomplet avec un document dont le champ documentManquant
     * est absent (null ou chaîne vide) doit lever une violation ciblée
     * sur documentsManquants[0].documentManquant.
     *
     * @dataProvider provideDocumentManquantVideOuNull
     */
    public function testViolationSiDocumentSansContenu(?string $contenu): void
    {
        $doc = new DossierDocumentManquant();
        $doc->setDate(new \DateTime('2026-01-15'));
        $doc->setDocumentManquant($contenu);

        $dossier = new Dossier();
        $dossier->setIncomplet(true);
        $dossier->addDocumentsManquant($doc);

        $constraint = new DossierDocumentsManquants();
        $this->validator->validate($dossier, $constraint);

        $this->buildViolation($constraint->messageChampsManquants)
            ->atPath('property.path.documentsManquants[0].documentManquant')
            ->assertRaised()
        ;
    }

    /**
     * @return iterable<string, array{0: string|null}>
     */
    public static function provideDocumentManquantVideOuNull(): iterable
    {
        yield 'null' => [null];
        yield 'chaîne vide' => [''];
        yield 'espaces seuls' => ['   '];
    }

    /**
     * Un dossier incomplet avec un document valide (date + documentManquant renseignés)
     * ne doit pas lever de violation. dateReception reste nullable.
     */
    public function testPasDErreurSiDocumentComplet(): void
    {
        $doc = new DossierDocumentManquant();
        $doc->setDate(new \DateTime('2026-01-15'));
        $doc->setDocumentManquant('Plans de masse, notice de sécurité');
        // dateReception volontairement absente

        $dossier = new Dossier();
        $dossier->setIncomplet(true);
        $dossier->addDocumentsManquant($doc);

        $this->validator->validate($dossier, new DossierDocumentsManquants());

        $this->assertNoViolation();
    }

    /**
     * Un dossier incomplet avec un document complet ET une dateReception renseignée
     * ne doit pas lever de violation (dateReception n'est pas vérifiée).
     */
    public function testPasDErreurAvecDateReceptionRenseignee(): void
    {
        $doc = new DossierDocumentManquant();
        $doc->setDate(new \DateTime('2026-01-15'));
        $doc->setDocumentManquant('Plans de masse');
        $doc->setDateReception(new \DateTime('2026-02-01'));

        $dossier = new Dossier();
        $dossier->setIncomplet(true);
        $dossier->addDocumentsManquant($doc);

        $this->validator->validate($dossier, new DossierDocumentsManquants());

        $this->assertNoViolation();
    }

    /**
     * Avec plusieurs documents dont un invalide, une violation doit être levée
     * sur le document invalide uniquement (chemin indexé).
     */
    public function testViolationCibleeParIndexSurDocumentInvalide(): void
    {
        $docValide = new DossierDocumentManquant();
        $docValide->setDate(new \DateTime('2026-01-10'));
        $docValide->setDocumentManquant('Plans de masse');

        $docInvalide = new DossierDocumentManquant();
        $docInvalide->setDate(new \DateTime('2026-02-01'));
        // documentManquant intentionnellement absent

        $dossier = new Dossier();
        $dossier->setIncomplet(true);
        $dossier->addDocumentsManquant($docValide);
        $dossier->addDocumentsManquant($docInvalide);

        $constraint = new DossierDocumentsManquants();
        $this->validator->validate($dossier, $constraint);

        $this->buildViolation($constraint->messageChampsManquants)
            ->atPath('property.path.documentsManquants[1].documentManquant')
            ->assertRaised()
        ;
    }
}
