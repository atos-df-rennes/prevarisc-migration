# TODO - Création dates commission/visite depuis calendrier

## 🎯 Contexte

Actuellement, les utilisateurs peuvent **sélectionner des dates existantes** dans le calendrier pour remplir les champs "Date de visite" et "Date de commission en salle".

La fonctionnalité de **création de nouvelles dates** (select event FullCalendar) est temporairement désactivée car elle nécessite :
1. Création d'une modale Bootstrap 3
2. Formulaire Symfony pour création date
3. Route backend Symfony

## 📋 Fonctionnalité manquante

### Legacy (Zend)
```javascript
// Lors de la sélection d'une plage de dates vide dans le calendrier :
select: function(start, end, allDay) {
    // 1. Ouvre my_dialog() jQuery UI
    // 2. Affiche formulaire /calendrier-des-commissions/dialogcomm
    // 3. POST vers /calendrier-des-commissions/adddates
    // 4. Rafraîchit calendrier + remplit input date dossier
}
```

### Comportement attendu
1. **Utilisateur clique sur jour vide** → Modale Bootstrap 3 s'ouvre
2. **Formulaire affiché** :
   - Date début / fin (pré-rempli)
   - Heure début / fin
   - Libellé (pré-rempli : "Nature - Établissement")
   - Checkbox "Répéter" (dates liées)
   - Type (commission/visite - selon calendrier)
3. **Submit formulaire** → AJAX POST vers route Symfony
4. **Succès** → Événement apparaît dans calendrier + input rempli

## 🔧 Implémentation requise

### 1. Template modale (Twig)
```twig
{# templates/dossier/_modal_create_date.html.twig #}
<div class="modal fade" id="modalCreateDate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Créer une date</h4>
            </div>
            <div class="modal-body">
                {# Formulaire chargé dynamiquement via AJAX #}
            </div>
        </div>
    </div>
</div>
```

### 2. FormType Symfony
```php
// src/Form/DateCommissionType.php
class DateCommissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateDebut', DateTimeType::class, ['label' => 'Date début'])
            ->add('dateFin', DateTimeType::class, ['label' => 'Date fin'])
            ->add('libelle', TextType::class, ['label' => 'Libellé'])
            ->add('repeter', CheckboxType::class, ['required' => false, 'label' => 'Répéter'])
            ->add('type', HiddenType::class); // 1=commission, 2/3=visite
    }
}
```

### 3. Routes Symfony
```yaml
# config/routes/dossier.yaml
dossier_calendar_create_form:
    path: /dossier/calendar/create-date-form
    controller: App\Controller\DossierController::createDateForm
    methods: [GET]

dossier_calendar_create:
    path: /dossier/calendar/create-date
    controller: App\Controller\DossierController::createDate
    methods: [POST]
```

### 4. Controller
```php
// src/Controller/DossierController.php

/**
 * Affiche formulaire création date (AJAX)
 */
public function createDateForm(Request $request): Response
{
    $form = $this->createForm(DateCommissionType::class);
    
    return $this->render('dossier/_form_create_date.html.twig', [
        'form' => $form->createView(),
        'dateDebut' => $request->query->get('dateD'),
        'dateFin' => $request->query->get('dateF'),
        'libelle' => $request->query->get('libelleCom'),
        'type' => $request->query->get('type'),
    ]);
}

/**
 * Crée date commission/visite (AJAX)
 */
public function createDate(Request $request, DateCommissionService $service): JsonResponse
{
    $form = $this->createForm(DateCommissionType::class);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $dateCommission = $service->createDate($data);
        
        return $this->json([
            'success' => true,
            'event' => [
                'id' => $dateCommission->getId(),
                'title' => $dateCommission->getLibelle(),
                'start' => $dateCommission->getDateDebut()->format('Y-m-d H:i:s'),
                'end' => $dateCommission->getDateFin()->format('Y-m-d H:i:s'),
                'className' => 'display-1',
            ]
        ]);
    }
    
    return $this->json(['success' => false, 'errors' => $form->getErrors()], 400);
}
```

### 5. JavaScript (calendar-legacy.js)
```javascript
// Réactiver select event
select: function(start, end, allDay) {
    var dateSelected = new Date(start);
    
    // Récupérer données dossier
    var nomEtab = $('#infosEtablissement .etab').text().trim();
    var nomNature = $('#selectNature option:selected').text().trim();
    var nomCom = nomNature + ' - ' + nomEtab;
    
    var type = (typeDossier == 1) ? 1 : 
               (infoCalendar == 'showCommission') ? 1 : typeDossier;
    
    // Charger formulaire via AJAX
    $.get('/dossier/calendar/create-date-form', {
        dateD: start.getTime(),
        dateF: end.getTime(),
        libelleCom: nomCom,
        type: type
    }, function(html) {
        $('#modalCreateDate .modal-body').html(html);
        $('#modalCreateDate').modal('show');
    });
}

// Submit formulaire
$(document).on('submit', '#formDateComm', function(e) {
    e.preventDefault();
    
    $.post('/dossier/calendar/create-date', $(this).serialize(), function(response) {
        if (response.success) {
            // Ajouter événement au calendrier
            calendar.fullCalendar('renderEvent', response.event);
            
            // Remplir input date dossier
            var date = new Date(response.event.start);
            var dd = ('0' + date.getDate()).slice(-2);
            var mm = ('0' + (date.getMonth() + 1)).slice(-2);
            var yyyy = date.getFullYear();
            
            if (type == 1) {
                $('#DATECOMM_INPUT').val(dd + '/' + mm + '/' + yyyy);
                $('#ID_AFFECTATION_DOSSIER_COMMISSION').val(response.event.id);
                $('#hideCommission').click();
            } else {
                $('#DATEVISITE_INPUT').val(dd + '/' + mm + '/' + yyyy);
                $('#ID_AFFECTATION_DOSSIER_VISITE').val(response.event.id);
                $('#hideVisite').click();
            }
            
            $('#modalCreateDate').modal('hide');
        }
    });
});
```

## ✅ Tests à effectuer

1. **Ouvrir calendrier** → Sélectionner plage dates vide
2. **Modale s'ouvre** → Formulaire pré-rempli
3. **Submit valide** → Événement apparaît + input rempli
4. **Submit invalide** → Erreurs affichées
5. **Fermer modale** → Calendrier reste ouvert
6. **Checkbox répéter** → Crée dates liées (visites)

## 📦 Dépendances

- ✅ Bootstrap 3 modal (déjà disponible)
- ✅ jQuery (déjà disponible)
- ✅ FullCalendar v1 (déjà intégré)
- ⚠️  DateCommissionService à créer
- ⚠️  Entity DateCommission (vérifier si existe)

## 📝 Notes

- **Priorité** : Moyenne (fonctionnalité de confort)
- **Alternative actuelle** : Utilisateurs créent dates via menu "Calendrier" puis les sélectionnent
- **Temps estimé** : 4-6h (modale + form + service + tests)
- **Référence legacy** : `prevarisc/application/views/scripts/dossier/index.phtml` lignes 550-628

---

**Statut** : 🔴 Non implémenté (désactivé dans calendar-legacy.js ligne 112)
