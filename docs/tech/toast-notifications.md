# Notifications toast

Le composant `public/js/toast-notifications.js` ajoute l'API `$.toast` à jQuery. Le JavaScript et le CSS sont inclus depuis `templates/base.html.twig`.

## API

```javascript
$.toast.success('Enregistrement effectué');
$.toast.error('Échec de l’enregistrement');
$.toast.warning('Vérifier les données');
$.toast.info('Traitement en cours');

const toastId = $.toast.info('Traitement en cours', { autoClose: false });
$.toast.close(toastId);
```

`$.toast.fromResponse(response)` accepte une réponse contenant `success` :

- `success: true` affiche `message`, ou le message de succès par défaut ;
- `success: false` affiche `error`, puis `message`, ou le message d'erreur par défaut ;
- si `success` n'est ni `true` ni `false`, aucun toast n'est affiché.

## Options

Les valeurs par défaut sont `autoClose: true`, `autoCloseDelay: 3000` ms, `closeButton: true` et une position `top-right`. Le CSS définit également les styles des positions gauche et basse.

**Limite actuelle :** l'option `position` passée à un appel n'est pas appliquée au conteneur. Sa classe est créée à partir de la position par défaut du script. Modifier la position par défaut nécessite d'adapter `DEFAULT_CONFIG` dans le JavaScript.

## Chargement et sécurité

Le composant dépend de jQuery, Bootstrap 3 et de son plugin Alert pour le bouton de fermeture. Ces bibliothèques sont chargées avant les toasts dans `templates/base.html.twig`.

Le message est inséré comme HTML (`.html()`), pas comme texte échappé. Ne jamais transmettre à l'API une chaîne contenant du contenu utilisateur non fiable.

Fichiers concernés : `public/js/toast-notifications.js`, `public/css/toast-notifications.css`.
