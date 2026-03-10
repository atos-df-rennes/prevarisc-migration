# 🔔 Système de Notifications Toast

Documentation du système de notifications Toast avec Bootstrap 3.

## 📦 Installation

### 1. Inclure les fichiers dans le template

```html
<!-- CSS -->
<link rel="stylesheet" href="/css/toast-notifications.css">

<!-- JavaScript (après jQuery et Bootstrap) -->
<script src="/js/toast-notifications.js"></script>
```

---

## 🚀 Utilisation

### Notifications simples

```javascript
// Succès
$.toast.success('Opération réussie !');

// Erreur
$.toast.error('Une erreur est survenue');

// Avertissement
$.toast.warning('Attention, action non recommandée');

// Information
$.toast.info('Information importante');
```

---

### Depuis une réponse JSON Symfony

```javascript
$.ajax({
    url: '/api/some-endpoint',
    method: 'POST',
    data: { ... },
    success: function(response) {
        // Affiche automatiquement success/error selon response.success
        $.toast.fromResponse(response);
    },
    error: function() {
        $.toast.error('Erreur réseau');
    }
});
```

**Format de réponse attendu :**
```json
{
    "success": true,
    "message": "Opération réussie"
}
```

ou

```json
{
    "success": false,
    "error": "Message d'erreur"
}
```

---

## ⚙️ Options de configuration

### Options disponibles

```javascript
$.toast.success('Message', {
    autoClose: true,           // Fermeture automatique
    autoCloseDelay: 3000,      // Délai en ms (défaut: 3000)
    closeButton: true,         // Afficher bouton fermer
    position: 'top-right'      // Position du conteneur
});
```

### Positions disponibles

- `'top-right'` (défaut)
- `'top-left'`
- `'bottom-right'`
- `'bottom-left'`

---

## 🎨 Personnalisation

### Modifier la durée par défaut

```javascript
// Dans toast-notifications.js, ligne 16
const DEFAULT_CONFIG = {
    autoClose: true,
    autoCloseDelay: 5000, // 5 secondes au lieu de 3
    closeButton: true,
    position: 'top-right',
};
```

### Modifier les styles CSS

Voir `public/css/toast-notifications.css` :
- `.toast-notification` : Style général
- `.toast-notification.alert-success` : Style succès
- `.toast-notification.alert-danger` : Style erreur
- etc.

---

## 📱 Responsive

Les notifications s'adaptent automatiquement sur mobile :
- Largeur pleine écran
- Marges réduites
- Position centrée

---

## 🔧 Intégration avec Symfony

### Controller

```php
public function myAction(): JsonResponse
{
    try {
        // ... logique métier ...
        
        return $this->json([
            'success' => true,
            'message' => 'Opération réussie',
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

### JavaScript

```javascript
$.ajax({
    url: '/my-route',
    method: 'POST',
    success: function(response) {
        $.toast.fromResponse(response);
    }
});
```

---

## 💡 Exemples d'utilisation

### Validation de formulaire

```javascript
$('#myForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                $.toast.success('Formulaire enregistré !');
                // Redirection ou autre action...
            } else {
                $.toast.error(response.error || 'Erreur de validation');
            }
        }
    });
});
```

### Confirmation d'action

```javascript
$('.delete-button').on('click', function() {
    if (!confirm('Êtes-vous sûr ?')) {
        return;
    }
    
    $.ajax({
        url: '/delete/' + $(this).data('id'),
        method: 'DELETE',
        success: function(response) {
            $.toast.fromResponse(response);
        }
    });
});
```

### Avec options personnalisées

```javascript
$.toast.warning('Cette action est irréversible', {
    autoClose: false,           // Ne pas fermer automatiquement
    position: 'bottom-right'    // Position en bas à droite
});
```

---

## 🐛 Dépannage

### Les notifications ne s'affichent pas

1. Vérifier que jQuery et Bootstrap sont chargés **avant** toast-notifications.js
2. Vérifier la console pour les erreurs JavaScript
3. Vérifier que le CSS est bien chargé

### Les animations ne fonctionnent pas

1. Vérifier que Bootstrap CSS est chargé (transitions CSS)
2. Vérifier qu'il n'y a pas de conflit CSS

### Le conteneur est mal positionné

1. Vérifier le z-index (9999 par défaut)
2. Vérifier qu'il n'y a pas de CSS qui surcharge `.toast-notification-container`

---

## 📝 Changelog

### v1.0.0 - 2026-01-09
- ✨ Création du système de notifications Toast
- ✅ Support Bootstrap 3
- ✅ Fermeture automatique configurable
- ✅ 4 positions disponibles
- ✅ Responsive mobile
- ✅ Intégration réponses JSON Symfony
