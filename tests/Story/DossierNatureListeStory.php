<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Constant\DossierNature as DossierNatureId;
use Doctrine\DBAL\Connection;
use Zenstruck\Foundry\Story;

final class DossierNatureListeStory extends Story
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function build(): void
    {
        foreach ([
            [1, 'Permis de construire (PC)', 1, 2],
            [2, 'Autorisation de travaux (AT)', 1, 1],
            [3, 'Dérogation', 1, 11],
            [4, 'Cahier des charges fonctionnel du SSI', 1, 12],
            [5, 'Cahier des charges de type T', 1, 16],
            [6, 'Salon type T', 1, 7],
            [DossierNatureId::LEVEE_PRESCRIPTIONS, 'Levée de prescriptions', 1, 5],
            [8, 'Documents divers', 1, 22],
            [9, 'Changement de DUS (Directeur unique de sécurité)', 1, 8],
            [10, 'Suivi organisme formation SSIAP', 1, 9],
            [11, 'Demande de registre de sécurité CTS', 1, 15],
            [12, "Demande d'implantation CTS < 6mois", 1, 6],
            [13, "Demande d'implantation CTS > 6mois", 1, 14],
            [14, "Permis d'aménager", 1, 18],
            [15, 'Permis de démolir', 1, 19],
            [16, "CR de visite des organismes d'inspection de sécurité incendie (GA)", 1, 17],
            [17, 'Etude suite à un avis "différé"', 1, 13],
            [18, 'Utilisation exceptionnelle de locaux', 1, 4],
            [DossierNatureId::LEVEE_AVIS_DEFAVORABLE, 'Levée de réserves suite à un avis défavorable', 1, 3],
            [20, 'Réception de travaux', 2, 2],
            [21, 'Périodique', 2, 1],
            [22, 'Chantier', 2, 6],
            [23, 'Contrôle', 2, 4],
            [24, 'Inopinée', 2, 5],
            [25, 'Réception de travaux', 3, 2],
            [26, 'Périodique', 3, 1],
            [27, 'Chantier', 3, 6],
            [28, 'Contrôle', 3, 4],
            [29, 'Inopinée', 3, 5],
            [30, 'Déclaration préalable', 1, 20],
            [31, 'Locaux SDIS', 4, 1],
            [32, 'Extérieur SDIS', 4, 2],
            [33, 'RVRMD (diag sécu)', 1, 21],
            [37, 'Incendie', 6, 1],
            [38, 'SAP', 6, 2],
            [39, 'Inter. div.', 6, 3],
            [40, 'Ouverture', 7, 1],
            [41, 'Fermeture', 7, 2],
            [42, 'Mise en demeure', 7, 3],
            [43, 'Téléphonique', 4, 3],
            [44, 'Utilisation exceptionnelle de locaux', 7, 4],
            [45, 'Courrier', 7, 5],
            [DossierNatureId::ECHEANCIER_TRAVAUX, 'Echéancier de travaux', 1, 10],
            [47, 'Avant ouverture', 2, 3],
            [48, 'Avant ouverture', 3, 3],
            [49, "Rapport d'organisme agréé", 5, 5],
            [50, 'Demande de visite hors programme', 5, 10],
            [51, 'Avis écrit motivé', 5, 3],
            [52, 'Lettre', 5, 1],
            [53, 'Consultation PLU', 5, 4],
            [54, 'Demande de renseignements', 5, 6],
            [55, 'Mise en demeure', 5, 2],
            [57, 'Demande de visite technique', 5, 8],
            [58, 'Demande de visite inopinée', 5, 9],
            [59, 'Demande de visite périodique', 5, 7],
            [60, 'Demande de visite de réception', 5, 11],
            [61, "Autorisation d'une ICPE", 1, 23],
            [62, 'Certificats d’urbanisme (CU)', 1, 24],
            [63, "Demande d'organisation de manifestation temporaire", 1, 25],
            [64, 'Visite conseil', 2, 7],
            [65, 'Autorisation de travaux', 5, 12],
            [66, 'Déclassement / Reclassement', 1, 26],
        ] as [$id, $libelle, $typeId, $ordre]) {
            $this->connection->insert('dossiernatureliste', [
                'ID_DOSSIERNATURE' => $id,
                'LIBELLE_DOSSIERNATURE' => $libelle,
                'ID_DOSSIERTYPE' => $typeId,
                'ORDRE' => $ordre,
            ]);
        }
    }
}
