/**
 * Système de notifications Toast avec Bootstrap 3
 * Compatible avec les réponses JSON des routes Symfony
 */
(function($) {
    'use strict';

    /**
     * Conteneur des notifications (créé dynamiquement si nécessaire)
     */
    const TOAST_CONTAINER_ID = 'toast-notification-container';
    
    /**
     * Configuration par défaut
     */
    const DEFAULT_CONFIG = {
        autoClose: true,
        autoCloseDelay: 3000, // 3 secondes
        closeButton: true,
        position: 'top-right', // top-right, top-left, bottom-right, bottom-left
    };

    /**
     * Classes Bootstrap 3 pour les différents types d'alertes
     */
    const ALERT_TYPES = {
        success: 'alert-success',
        error: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info',
    };

    /**
     * Icônes Glyphicons pour chaque type
     */
    const ALERT_ICONS = {
        success: 'glyphicon-ok-sign',
        error: 'glyphicon-exclamation-sign',
        warning: 'glyphicon-warning-sign',
        info: 'glyphicon-info-sign',
    };

    /**
     * Initialise le conteneur de notifications si nécessaire
     */
    function ensureToastContainer() {
        let $container = $('#' + TOAST_CONTAINER_ID);
        
        if ($container.length === 0) {
            $container = $('<div>', {
                id: TOAST_CONTAINER_ID,
                class: 'toast-notification-container toast-' + DEFAULT_CONFIG.position
            });
            $('body').append($container);
        }
        
        return $container;
    }

    /**
     * Affiche une notification toast
     * 
     * @param {string} type - Type de notification (success, error, warning, info)
     * @param {string} message - Message à afficher
     * @param {object} options - Options de configuration
     */
    function showToast(type, message, options) {
        options = $.extend({}, DEFAULT_CONFIG, options || {});
        
        const alertClass = ALERT_TYPES[type] || ALERT_TYPES.info;
        const iconClass = ALERT_ICONS[type] || ALERT_ICONS.info;
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Créer l'élément toast
        const $toast = $('<div>', {
            id: toastId,
            class: 'alert ' + alertClass + ' alert-dismissible toast-notification fade',
            role: 'alert'
        });
        
        // Ajouter le bouton de fermeture si demandé
        if (options.closeButton) {
            const $closeBtn = $('<button>', {
                type: 'button',
                class: 'close',
                'data-dismiss': 'alert',
                'aria-label': 'Fermer'
            }).html('&times;');
            
            $toast.append($closeBtn);
        }
        
        // Ajouter l'icône et le message
        const $icon = $('<span>', {
            class: 'glyphicon ' + iconClass,
            'aria-hidden': 'true'
        });
        
        const $content = $('<span>').html(' ' + message);
        
        $toast.append($icon).append($content);
        
        // Ajouter au conteneur
        const $container = ensureToastContainer();
        $container.append($toast);
        
        // Animation d'entrée
        setTimeout(function() {
            $toast.addClass('in');
        }, 10);
        
        // Fermeture automatique
        if (options.autoClose) {
            setTimeout(function() {
                closeToast($toast);
            }, options.autoCloseDelay);
        }
        
        return toastId;
    }

    /**
     * Ferme une notification avec animation
     */
    function closeToast($toast) {
        $toast.removeClass('in');
        
        setTimeout(function() {
            $toast.alert('close');
        }, 150); // Durée de la transition CSS
    }

    /**
     * Affiche une notification depuis une réponse JSON Symfony
     * 
     * @param {object} response - Réponse JSON avec { success: bool, error?: string, message?: string }
     * @param {object} options - Options de configuration
     */
    function showToastFromResponse(response, options) {
        if (response.success === true) {
            const message = response.message || 'Opération réussie';
            return showToast('success', message, options);
        } else if (response.success === false) {
            const message = response.error || response.message || 'Une erreur est survenue';
            return showToast('error', message, options);
        }
    }

    /**
     * Raccourcis pour les différents types
     */
    $.toast = {
        success: function(message, options) {
            return showToast('success', message, options);
        },
        error: function(message, options) {
            return showToast('error', message, options);
        },
        warning: function(message, options) {
            return showToast('warning', message, options);
        },
        info: function(message, options) {
            return showToast('info', message, options);
        },
        close: function(id) {
            closeToast($('#' + id));
        },
        fromResponse: showToastFromResponse
    };

})(jQuery);
