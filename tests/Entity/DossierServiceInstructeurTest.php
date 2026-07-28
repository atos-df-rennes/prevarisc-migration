<?php

namespace App\Tests\Entity;

use App\Entity\Dossier;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour la normalisation du champ serviceInstructeur dans l'entité Dossier.
 *
 * Contexte : Le legacy insère "0" comme valeur par défaut lors de la création de dossiers.
 * Ces valeurs doivent être normalisées en null côté Symfony pour une UX cohérente.
 *
 * TODO: Quand création dossier migrée dans Symfony, ajouter migration SQL pour nettoyer la BDD.
 */
class DossierServiceInstructeurTest extends TestCase
{
    private Dossier $dossier;

    protected function setUp(): void
    {
        $this->dossier = new Dossier();
    }

    /**
     * Test : Valeur "0" legacy normalisée en null au getter.
     */
    public function testGetServiceInstructeurNormalizeLegacyZero(): void
    {
        // Simuler valeur legacy "0" en base
        $reflection = new \ReflectionClass($this->dossier);
        $property = $reflection->getProperty('serviceInstructeur');
        $property->setValue($this->dossier, '0');

        // Vérifier normalisation
        $this->assertNull(
            $this->dossier->getServiceInstructeur(),
            'La valeur legacy "0" doit être normalisée en null'
        );
    }

    /**
     * Test : Chaîne vide legacy normalisée en null au getter.
     */
    public function testGetServiceInstructeurNormalizeLegacyEmptyString(): void
    {
        // Simuler valeur legacy "" en base
        $reflection = new \ReflectionClass($this->dossier);
        $property = $reflection->getProperty('serviceInstructeur');
        $property->setValue($this->dossier, '');

        // Vérifier normalisation
        $this->assertNull(
            $this->dossier->getServiceInstructeur(),
            'La chaîne vide legacy doit être normalisée en null'
        );
    }

    /**
     * Test : Valeur valide non modifiée.
     */
    public function testGetServiceInstructeurKeepsValidValue(): void
    {
        $this->dossier->setServiceInstructeur('SDIS 38');

        $this->assertSame(
            'SDIS 38',
            $this->dossier->getServiceInstructeur(),
            'Une valeur valide ne doit pas être modifiée'
        );
    }

    /**
     * Test : Null reste null.
     */
    public function testGetServiceInstructeurKeepsNull(): void
    {
        $this->dossier->setServiceInstructeur(null);

        $this->assertNull(
            $this->dossier->getServiceInstructeur(),
            'null doit rester null'
        );
    }

    /**
     * Test : Setter normalise "0" en null.
     */
    public function testSetServiceInstructeurNormalizesZero(): void
    {
        $this->dossier->setServiceInstructeur('0');

        $this->assertNull(
            $this->dossier->getServiceInstructeur(),
            'Setter doit normaliser "0" en null'
        );
    }

    /**
     * Test : Setter normalise chaîne vide en null.
     */
    public function testSetServiceInstructeurNormalizesEmptyString(): void
    {
        $this->dossier->setServiceInstructeur('');

        $this->assertNull(
            $this->dossier->getServiceInstructeur(),
            'Setter doit normaliser "" en null'
        );
    }

    /**
     * Test : Setter conserve valeur valide.
     */
    public function testSetServiceInstructeurKeepsValidValue(): void
    {
        $this->dossier->setServiceInstructeur('SDIS 69');

        $this->assertSame(
            'SDIS 69',
            $this->dossier->getServiceInstructeur(),
            'Setter doit conserver une valeur valide'
        );
    }

    /**
     * Test : Fluent interface du setter.
     */
    public function testSetServiceInstructeurReturnsThis(): void
    {
        $result = $this->dossier->setServiceInstructeur('SDIS 01');

        $this->assertSame(
            $this->dossier,
            $result,
            'Setter doit retourner $this pour fluent interface'
        );
    }
}
