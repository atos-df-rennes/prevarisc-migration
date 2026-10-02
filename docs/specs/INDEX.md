# Spécifications fonctionnelles — Prevarisc

Prevarisc aide les services départementaux d'incendie et de secours (SDIS) à suivre la prévention des risques dans les établissements recevant du public (ERP) et les autres établissements concernés. Les documents ci-dessous décrivent les principales fonctions de l'application et les règles observables.

## Modules

- [MOD-01 — Établissements](MOD-01-ETABLISSEMENTS.md)
- [MOD-02 — Dossiers](MOD-02-DOSSIERS.md)
- [MOD-03 — Commissions de sécurité](MOD-03-COMMISSIONS.md)
- [MOD-04 — Prescriptions réglementaires](MOD-04-PRESCRIPTIONS.md)
- [MOD-05 — Utilisateurs et droits d'accès](MOD-05-UTILISATEURS.md)
- [MOD-06 — Recherche et export](MOD-06-RECHERCHE.md)
- [MOD-07 — Groupements de communes](MOD-07-GROUPEMENTS.md)
- [MOD-08 — Contacts](MOD-08-CONTACTS.md)
- [MOD-09 — Pièces jointes](MOD-09-PIECES-JOINTES.md)
- [MOD-10 — Textes applicables](MOD-10-TEXTES-APPLICABLES.md)
- [MOD-11 — Périodicités](MOD-11-PERIODICITES.md)
- [MOD-12 — Tableau de bord](MOD-12-TABLEAU-DE-BORD.md)
- [MOD-13 — Plat'AU](MOD-13-PLATAU.md)
- [MOD-14 — Formulaires personnalisés](MOD-14-FORMULAIRES.md)
- [MOD-15 — Cartographie](MOD-15-CARTOGRAPHIE.md)
- [MOD-16 — Gestion des communes](MOD-16-COMMUNES.md)
- [MOD-17 — Documents modèles](MOD-17-DOCUMENTS-MODELES.md)
- [MOD-18 — Alertes et notifications](MOD-18-ALERTES.md)
- [MOD-19 — Statistiques et extractions](MOD-19-STATISTIQUES.md)
- [MOD-20 — Rétablissement d'éléments supprimés](MOD-20-RETABLISSEMENT.md)
- [MOD-21 — Administration](MOD-21-ADMINISTRATION.md)
- [MOD-22 — À propos et documentation](MOD-22-A-PROPOS.md)

## Relations entre les modules

| Besoin fonctionnel | Modules concernés |
|---|---|
| Suivre les établissements et les interventions réalisées | MOD-01 Établissements, MOD-02 Dossiers |
| Préparer les séances et déterminer les commissions compétentes | MOD-03 Commissions, MOD-07 Groupements de communes, MOD-17 Documents modèles |
| Appliquer les règles de prévention et les textes associés | MOD-04 Prescriptions, MOD-10 Textes applicables, MOD-11 Périodicités |
| Retrouver et exporter les établissements et les dossiers | MOD-06 Recherche |
| Compléter les fiches d'établissement et de dossier | MOD-08 Contacts, MOD-09 Pièces jointes, MOD-14 Formulaires personnalisés |
| Gérer les comptes et les informations communes à plusieurs fonctions | MOD-05 Utilisateurs et droits d'accès, MOD-12 Tableau de bord, MOD-18 Alertes et notifications |
| Échanger avec la plateforme d'urbanisme | MOD-02 Dossiers, MOD-09 Pièces jointes, MOD-13 Plat'AU |

Le module MOD-19 ne propose actuellement pas de fonction dédiée de statistiques et d'extractions. Les fonctions de recherche et d'export disponibles sont décrites dans MOD-06.
