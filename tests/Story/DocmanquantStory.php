<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class DocmanquantStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        // User-supplied reference list; IDs are fixture-local, not production IDs.
        foreach ([
            [1, 'Absence de transmission par la mairie'],
            [2, 'Le formulaire CERFA de PC, DP, PA ou AT. Ce document doit être daté et signé par le maître d’ouvrage, qui s’engage au respect des règles de construction.'],
            [3, 'Le dossier permettant de vérifier la conformité du projet avec les règles de sécurité, également appelé notice de sécurité, qui précisera les dispositions prises pour satisfaire aux mesures prévues par le règlement de sécurité et notamment le calcul de l’effectif, le ou les activités exercées, les risques particuliers…'],
            [4, 'La notice descriptive précisant les matériaux utilisés tant pour le gros œuvre que pour la décoration et les aménagements intérieurs.'],
            [5, 'Les plans de situation, de masse et de façades des constructions projetées faisant ressortir, d’une part les conditions d’accessibilité des engins de secours, et plus particulièrement les largeurs des voies et les emplacements des baies d’intervention pompiers, et, d’autre part, la présence de tout bâtiment ou local occupé par des tiers.'],
            [6, 'Les plans de détails indiquant les largeurs des passages affectés à la circulation du public, les dégagements, les escaliers, les sorties, les renseignements sommaires (organes généraux de production et de distribution d’électricité, emplacement des compteurs de gaz et cheminement des canalisations générales d’alimentation, emplacement et renseignements des chaufferies et des locaux à risques, moyens particuliers de défense et de secours contre l’incendie…).'],
            [7, 'Les plans de coupe et plans de niveaux, ainsi qu’éventuellement ceux des planchers intermédiaires aménagés dans la hauteur comprise entre deux niveaux ou entre le dernier plancher et la toiture du bâtiment.'],
            [8, 'Les plans avant et après projet.'],
            [9, 'Le cahier des charges fonctionnel du SSI (système de sécurité incendie), le cas échéant. Ce document est rédigé par la personne en charge de la coordination du SSI.'],
            [10, 'L’engagement solidité du maître d’ouvrage (l’engagement figurant sur les documents CERFA relatifs au permis de construire est suffisant).'],
            [11, 'L’accord du directeur unique de sécurité le cas échéant.'],
        ] as [$id, $libelle]) {
            $this->connection->insert('docmanquant', [
                'ID_DOCMANQUANT' => $id,
                'LIBELLE_DOCMANQUANT' => $libelle,
            ]);
        }
    }
}
