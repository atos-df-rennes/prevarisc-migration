<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\CommissionRegle;
use App\Entity\CommissionRegleEtudeVisite;
use App\Entity\CommissionRegleLocalSommeil;
use App\Form\CommissionRegleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Teste les event listeners de CommissionRegleType :
 *  - onPreSetData : extraction des collections vers des valeurs simples
 *  - onPostSubmit : synchronisation des collections à partir des valeurs soumises
 */
final class CommissionRegleTypeTest extends TestCase
{
    private CommissionRegleType $type;

    protected function setUp(): void
    {
        $this->type = new CommissionRegleType(1);
    }

    // =========================================================================
    // onPreSetData
    // =========================================================================

    public function testPreSetDataExtraitLocalSommeilEtEtudeVisite(): void
    {
        $regle = new CommissionRegle();
        $regle->addLocalSommeil($this->buildLocalSommeil(1));
        $regle->addEtudeVisite($this->buildEtudeVisite(0));

        $capturedOptions = [];
        $form = $this->buildFormMockCapturingAddOptions($capturedOptions);
        $event = $this->buildPreSetDataEvent($form, $regle);

        $this->type->onPreSetData($event);

        $this->assertSame([1], $capturedOptions['localSommeil']['data']);
        $this->assertSame([0], $capturedOptions['etudeVisite']['data']);
    }

    public function testPreSetDataAvecPlusieursValeurs(): void
    {
        $regle = new CommissionRegle();
        $regle->addLocalSommeil($this->buildLocalSommeil(0));
        $regle->addLocalSommeil($this->buildLocalSommeil(1));
        $regle->addEtudeVisite($this->buildEtudeVisite(1));

        $capturedOptions = [];
        $form = $this->buildFormMockCapturingAddOptions($capturedOptions);
        $event = $this->buildPreSetDataEvent($form, $regle);

        $this->type->onPreSetData($event);

        $this->assertSame([0, 1], $capturedOptions['localSommeil']['data']);
        $this->assertSame([1], $capturedOptions['etudeVisite']['data']);
    }

    public function testPreSetDataAvecCollectionsVidesDonneDatasVides(): void
    {
        $regle = new CommissionRegle();

        $capturedOptions = [];
        $form = $this->buildFormMockCapturingAddOptions($capturedOptions);
        $event = $this->buildPreSetDataEvent($form, $regle);

        $this->type->onPreSetData($event);

        $this->assertSame([], $capturedOptions['localSommeil']['data']);
        $this->assertSame([], $capturedOptions['etudeVisite']['data']);
    }

    public function testPreSetDataAvecDataNullDonneDatasVides(): void
    {
        // Cas prototype : getData() retourne null (nouvelle règle non initialisée)
        $capturedOptions = [];
        $form = $this->buildFormMockCapturingAddOptions($capturedOptions);
        $event = $this->buildPreSetDataEvent($form, null);

        $this->type->onPreSetData($event);

        $this->assertSame([], $capturedOptions['localSommeil']['data']);
        $this->assertSame([], $capturedOptions['etudeVisite']['data']);
    }

    // =========================================================================
    // onPostSubmit — localSommeil
    // =========================================================================

    public function testAjouteLocalSommeilManquantDansCollection(): void
    {
        $regle = new CommissionRegle();
        $event = $this->buildPostSubmitEvent($regle, [1], []);

        $this->type->onPostSubmit($event);

        $collection = $regle->getLocalSommeil();
        $this->assertCount(1, $collection);
        $this->assertSame(1, $collection->first()->getLocalSommeil());
    }

    public function testSupprimerLocalSommeilNonSoumis(): void
    {
        $regle = new CommissionRegle();
        $regle->addLocalSommeil($this->buildLocalSommeil(0));
        $regle->addLocalSommeil($this->buildLocalSommeil(1));

        // On soumet uniquement la valeur 1 → la valeur 0 doit être retirée
        $event = $this->buildPostSubmitEvent($regle, [1], []);

        $this->type->onPostSubmit($event);

        $collection = $regle->getLocalSommeil();
        $this->assertCount(1, $collection);
        $this->assertSame(1, $collection->first()->getLocalSommeil());
    }

    public function testNeDoublePasLocalSommeilDejaExistant(): void
    {
        $regle = new CommissionRegle();
        $regle->addLocalSommeil($this->buildLocalSommeil(1));

        $event = $this->buildPostSubmitEvent($regle, [1], []);

        $this->type->onPostSubmit($event);

        $this->assertCount(1, $regle->getLocalSommeil());
    }

    public function testViderLocalSommeilSiAucuneValeurSoumise(): void
    {
        $regle = new CommissionRegle();
        $regle->addLocalSommeil($this->buildLocalSommeil(1));

        $event = $this->buildPostSubmitEvent($regle, [], []);

        $this->type->onPostSubmit($event);

        $this->assertCount(0, $regle->getLocalSommeil());
    }

    // =========================================================================
    // onPostSubmit — etudeVisite
    // =========================================================================

    public function testAjouteEtudeVisiteManquanteDansCollection(): void
    {
        $regle = new CommissionRegle();
        $event = $this->buildPostSubmitEvent($regle, [], [0]);

        $this->type->onPostSubmit($event);

        $collection = $regle->getEtudeVisite();
        $this->assertCount(1, $collection);
        $this->assertSame(0, $collection->first()->getEtudeVisite());
    }

    public function testSupprimerEtudeVisiteNonSoumise(): void
    {
        $regle = new CommissionRegle();
        $regle->addEtudeVisite($this->buildEtudeVisite(0));
        $regle->addEtudeVisite($this->buildEtudeVisite(1));

        // On soumet uniquement la valeur 0 → la valeur 1 doit être retirée
        $event = $this->buildPostSubmitEvent($regle, [], [0]);

        $this->type->onPostSubmit($event);

        $collection = $regle->getEtudeVisite();
        $this->assertCount(1, $collection);
        $this->assertSame(0, $collection->first()->getEtudeVisite());
    }

    public function testNeDoublePasEtudeVisiteDejaExistante(): void
    {
        $regle = new CommissionRegle();
        $regle->addEtudeVisite($this->buildEtudeVisite(1));

        $event = $this->buildPostSubmitEvent($regle, [], [1]);

        $this->type->onPostSubmit($event);

        $this->assertCount(1, $regle->getEtudeVisite());
    }

    public function testViderEtudeVisiteSiAucuneValeurSoumise(): void
    {
        $regle = new CommissionRegle();
        $regle->addEtudeVisite($this->buildEtudeVisite(1));

        $event = $this->buildPostSubmitEvent($regle, [], []);

        $this->type->onPostSubmit($event);

        $this->assertCount(0, $regle->getEtudeVisite());
    }

    // =========================================================================
    // onPostSubmit — cas limites
    // =========================================================================

    public function testIgnorePostSubmitSiDataNestPasCommissionRegle(): void
    {
        // getData() retourne null → aucune exception, les collections ne sont pas modifiées
        $localSommeilField = $this->createMock(FormInterface::class);
        $localSommeilField->method('getData')->willReturn([1]);

        $etudeVisiteField = $this->createMock(FormInterface::class);
        $etudeVisiteField->method('getData')->willReturn([1]);

        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn(null);
        $form->method('get')->willReturnMap([
            ['localSommeil', $localSommeilField],
            ['etudeVisite', $etudeVisiteField],
        ]);

        $event = $this->createMock(FormEvent::class);
        $event->method('getForm')->willReturn($form);

        // Aucune exception ne doit être levée
        $this->type->onPostSubmit($event);
        $this->addToAssertionCount(1);
    }

    public function testLocalSommeilEtEtudeVisiteSontSynchronisesIndependamment(): void
    {
        $regle = new CommissionRegle();
        $regle->addLocalSommeil($this->buildLocalSommeil(0));
        $regle->addEtudeVisite($this->buildEtudeVisite(1));

        // On garde localSommeil=0 et on ajoute etudeVisite=0, on retire etudeVisite=1
        $event = $this->buildPostSubmitEvent($regle, [0], [0]);

        $this->type->onPostSubmit($event);

        $this->assertCount(1, $regle->getLocalSommeil());
        $this->assertSame(0, $regle->getLocalSommeil()->first()->getLocalSommeil());

        $this->assertCount(1, $regle->getEtudeVisite());
        $this->assertSame(0, $regle->getEtudeVisite()->first()->getEtudeVisite());
    }

    // =========================================================================
    // Builders
    // =========================================================================

    /**
     * Construit un mock de FormInterface dont la méthode add() capture les options
     * dans le tableau passé par référence.
     *
     * @param array<string, array<string, mixed>> $capturedOptions
     */
    private function buildFormMockCapturingAddOptions(array &$capturedOptions): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('add')->willReturnCallback(
            function (string $child, string $type, array $options) use (&$capturedOptions, $form): FormInterface {
                $capturedOptions[$child] = $options;

                return $form;
            }
        );

        return $form;
    }

    private function buildPreSetDataEvent(FormInterface $form, ?CommissionRegle $data): FormEvent
    {
        $event = $this->createMock(FormEvent::class);
        $event->method('getForm')->willReturn($form);
        $event->method('getData')->willReturn($data);

        return $event;
    }

    /**
     * @param array<int> $localSommeilValues
     * @param array<int> $etudeVisiteValues
     */
    private function buildPostSubmitEvent(CommissionRegle $regle, array $localSommeilValues, array $etudeVisiteValues): FormEvent
    {
        $localSommeilField = $this->createMock(FormInterface::class);
        $localSommeilField->method('getData')->willReturn($localSommeilValues);

        $etudeVisiteField = $this->createMock(FormInterface::class);
        $etudeVisiteField->method('getData')->willReturn($etudeVisiteValues);

        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn($regle);
        $form->method('get')->willReturnMap([
            ['localSommeil', $localSommeilField],
            ['etudeVisite', $etudeVisiteField],
        ]);

        $event = $this->createMock(FormEvent::class);
        $event->method('getForm')->willReturn($form);

        return $event;
    }

    private function buildLocalSommeil(int $value): CommissionRegleLocalSommeil
    {
        $ls = new CommissionRegleLocalSommeil();
        $ls->setLocalSommeil($value);

        return $ls;
    }

    private function buildEtudeVisite(int $value): CommissionRegleEtudeVisite
    {
        $ev = new CommissionRegleEtudeVisite();
        $ev->setEtudeVisite($value);

        return $ev;
    }
}
