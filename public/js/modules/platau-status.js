const STATUS_LABELS = {
    ok: "Plat'AU est disponible",
    warning: "Plat'AU est disponible (maintenance annoncée)",
    error: "Plat'AU est indisponible",
    unknown: "État de Plat'AU inconnu",
};

const STATUS_CLASSES = ['platau-status-pending', 'platau-status-ok', 'platau-status-warning', 'platau-status-error'];

function formatTime(isoDate) {
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return String(date.getHours()).padStart(2, '0') + 'h' + String(date.getMinutes()).padStart(2, '0');
}

function setHidden(element, hidden) {
    element.hidden = hidden;
}

/**
 * Charge l'état de Plat'AU (mis en cache côté serveur) et met à jour l'indicateur de l'en-tête.
 * Les messages renvoyés par le serveur sont déjà échappés (HTML).
 */
export function initPlatauStatus(root) {
    if (!root) {
        return;
    }

    const dot = root.querySelector('#platau-status-dot');
    const srLabel = root.querySelector('#platau-status-sr');
    const message = root.querySelector('#platau-status-message');
    const version = root.querySelector('#platau-status-version');
    const maintenance = root.querySelector('#platau-status-maintenance');
    const times = root.querySelector('#platau-status-times');

    const setStatus = (status) => {
        dot.classList.remove(...STATUS_CLASSES);
        dot.classList.add('platau-status-' + (status === 'unknown' ? 'error' : status));
        srLabel.textContent = STATUS_LABELS[status] ?? STATUS_LABELS.unknown;
    };

    const showUnknown = () => {
        setStatus('unknown');
        message.textContent = "Impossible de récupérer l'état de Plat'AU.";
        setHidden(version, true);
        setHidden(maintenance, true);
        setHidden(times, true);
    };

    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 15000);

    fetch(root.dataset.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal })
        .then((response) => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        })
        .then((data) => {
            setStatus(data.status);
            message.innerHTML = data.message ?? '';

            const versionParts = [];
            if (data.version?.version) {
                versionParts.push(data.version.version);
            }
            if (data.version?.versionModel) {
                versionParts.push('Modèle : ' + data.version.versionModel);
            }
            if (data.version?.versionNomenclature) {
                versionParts.push('Nomenclature : ' + data.version.versionNomenclature);
            }
            // En succès, la version est déjà incluse dans le message
            version.textContent = versionParts.join(' / ');
            setHidden(version, versionParts.length === 0 || data.success);

            if (data.maintenance?.message) {
                maintenance.innerHTML = data.maintenance.message;
                setHidden(maintenance, false);
            } else {
                setHidden(maintenance, true);
            }

            const checkedAt = formatTime(data.checkedAt);
            const nextCheckAt = formatTime(data.nextCheckAt);
            if (checkedAt && nextCheckAt) {
                times.textContent = 'Dernière vérification : ' + checkedAt + ' · Prochaine vérification : ' + nextCheckAt;
                setHidden(times, false);
            }
        })
        .catch(showUnknown)
        .finally(() => window.clearTimeout(timeoutId));
}
