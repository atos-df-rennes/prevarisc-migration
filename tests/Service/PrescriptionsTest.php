<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionReglementaireAssociation;
use App\Entity\PrescriptionTexteListe;
use App\Repository\PrescriptionArticleListeRepository;
use App\Repository\PrescriptionReglementaireAssociationRepository;
use App\Repository\PrescriptionReglementaireRepository;
use App\Repository\PrescriptionTexteListeRepository;
use App\Repository\PrescriptionTypeAssociationRepository;
use App\Repository\PrescriptionTypeRepository;
use App\Service\Prescriptions;
use PHPUnit\Framework\TestCase;

final class PrescriptionsTest extends TestCase
{
    private Prescriptions $prescriptions;

    protected function setUp(): void
    {
        $this->prescriptions = new Prescriptions(
            $this->createStub(PrescriptionTypeRepository::class),
            $this->createStub(PrescriptionTypeAssociationRepository::class),
            $this->createStub(PrescriptionTexteListeRepository::class),
            $this->createStub(PrescriptionArticleListeRepository::class),
            $this->createStub(PrescriptionReglementaireRepository::class),
            $this->createStub(PrescriptionReglementaireAssociationRepository::class)
        );
    }

    /**
     * Teste la méthode `callBuildAssociationsFromRaw` avec une entrée vide.
     *
     * @throws \Exception si une assertion échoue ou si un problème survient lors de l'exécution
     */
    public function testBuildAssociationsFromRawWithEmptyInput(): void
    {
        $result = $this->callBuildAssociationsFromRaw([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Teste la méthode `callBuildAssociationsFromRaw` avec une entrée valide.
     *
     * Cette méthode crée des mocks pour les entités `PrescriptionReglementaireAssociation`,
     * `PrescriptionTexteListe` et `PrescriptionArticleListe`. Elle simule un ensemble de données brutes
     * pour vérifier le bon fonctionnement de la méthode `callBuildAssociationsFromRaw`.
     * Les assertions permettent de valider que le résultat obtenu est conforme aux attentes.
     */
    public function testBuildAssociationsFromRawWithValidInput(): void
    {
        // Mocking entities
        $association1 = $this->createStub(PrescriptionReglementaireAssociation::class);
        $association1->method('getTexte')->willReturn(1);
        $association1->method('getArticle')->willReturn(101);

        $association2 = $this->createStub(PrescriptionReglementaireAssociation::class);
        $association2->method('getTexte')->willReturn(1);
        $association2->method('getArticle')->willReturn(102);

        $association3 = $this->createStub(PrescriptionReglementaireAssociation::class);
        $association3->method('getTexte')->willReturn(1);
        $association3->method('getArticle')->willReturn(101);

        $texte1 = $this->createStub(PrescriptionTexteListe::class);
        $texte1->method('getId')->willReturn(1);

        $article1 = $this->createStub(PrescriptionArticleListe::class);
        $article1->method('getId')->willReturn(101);

        $article2 = $this->createStub(PrescriptionArticleListe::class);
        $article2->method('getId')->willReturn(102);

        $rawItems = [$association1, $texte1, $article1, $association2, $article2, $association3];

        $result = $this->callBuildAssociationsFromRaw($rawItems);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertArrayHasKey('association', $result[0]);
        $this->assertArrayHasKey('texte', $result[0]);
        $this->assertArrayHasKey('article', $result[0]);

        $this->assertSame($association1, $result[0]['association']);
        $this->assertSame($texte1, $result[0]['texte']);
        $this->assertSame($article1, $result[0]['article']);

        $this->assertSame($association2, $result[1]['association']);
        $this->assertSame($texte1, $result[1]['texte']);
        $this->assertSame($article2, $result[1]['article']);

        $this->assertSame($association3, $result[2]['association']);
        $this->assertSame($texte1, $result[2]['texte']);
        $this->assertSame($article1, $result[2]['article']);
    }

    /**
     * Teste la méthode `buildAssociationsFromRaw` avec des données d'association qui manquent de texte ou d'article.
     *
     * Ce test vérifie que la méthode peut gérer correctement une association où
     * les propriétés `texte` et `article` sont nulles.
     *
     * @throws \ReflectionException si la classe ou la méthode privée appelée via réflexion n'existent pas
     */
    public function testBuildAssociationsFromRawWithMissingTextOrArticle(): void
    {
        // Mocking association without texte and article
        $association1 = $this->createStub(PrescriptionReglementaireAssociation::class);
        $association1->method('getTexte')->willReturn(null);
        $association1->method('getArticle')->willReturn(null);

        $rawItems = [$association1];

        $result = $this->callBuildAssociationsFromRaw($rawItems);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($association1, $result[0]['association']);
        $this->assertNull($result[0]['texte']);
        $this->assertNull($result[0]['article']);
    }

    /**
     * Teste la méthode `callBuildAssociationsFromRaw` en fournissant des entités non liées.
     *
     * Cette méthode vérifie que la fonction gère correctement un tableau d'entités
     * qui ne sont pas associées aux données attendues, en retournant une liste vide.
     */
    public function testBuildAssociationsFromRawWithUnrelatedEntities(): void
    {
        // Mocking unrelated entities
        $texte1 = $this->createStub(PrescriptionTexteListe::class);
        $texte1->method('getId')->willReturn(1);

        $rawItems = [$texte1];

        $result = $this->callBuildAssociationsFromRaw($rawItems);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Appelle la méthode privée `buildAssociationsFromRaw` définie dans la classe de l'objet `$this->prescriptions`.
     *
     * @param array $items les données brutes qui seront utilisées pour l'association
     *
     * @return mixed le résultat de l'exécution de la méthode `buildAssociationsFromRaw`
     *
     * @throws \ReflectionException si la classe ou la méthode n'existent pas
     */
    private function callBuildAssociationsFromRaw(array $items): mixed
    {
        $reflectionClass = new \ReflectionClass($this->prescriptions);
        $method = $reflectionClass->getMethod('buildAssociationsFromRaw');

        return $method->invoke($this->prescriptions, $items);
    }
}
