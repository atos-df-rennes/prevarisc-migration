<?php

namespace App\Dto\FusionDesCommunes;

/**
 * Résultat d'un traitement de fusion de communes : distingue les lots réussis
 * des lots ayant échoué fonctionnellement, afin de composer un message
 * utilisateur (succès global, succès partiel avec avertissements, etc.).
 */
final class ResultatFusion
{
    /** @var list<string> */
    private $succes = [];

    /** @var list<array{nom: string, message: string}> */
    private $echecs = [];

    public function ajouterSucces(string $nom): void
    {
        $this->succes[] = $nom;
    }

    public function ajouterEchec(string $nom, string $message): void
    {
        $this->echecs[] = ['nom' => $nom, 'message' => $message];
    }

    /**
     * @return list<string>
     */
    public function succes(): array
    {
        return $this->succes;
    }

    /**
     * @return list<array{nom: string, message: string}>
     */
    public function echecs(): array
    {
        return $this->echecs;
    }

    public function aDesSucces(): bool
    {
        return [] !== $this->succes;
    }

    public function aDesEchecs(): bool
    {
        return [] !== $this->echecs;
    }
}
