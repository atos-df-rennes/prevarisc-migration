/**
 * Charge en arrière-plan l'indicateur Plat'AU de l'en-tête lorsque son état n'était pas en cache au rendu de la page.
 * Le serveur renvoie le fragment HTML complet de l'indicateur ; on met à jour l'élément existant
 * pour conserver la zone aria-live et l'état ouvert du menu.
 */
export function initPlatauStatus(root) {
    if (!root?.dataset.url) {
        return;
    }

    const dot = root.querySelector('#platau-status-dot');
    const srLabel = root.querySelector('#platau-status-sr');

    const showUnknown = () => {
        dot.classList.replace('platau-status-pending', 'platau-status-error');
        srLabel.textContent = "État de Plat'AU inconnu";
        root.querySelector('#platau-status-message').textContent = "Impossible de récupérer l'état de Plat'AU.";
    };

    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 15000);

    fetch(root.dataset.url, { headers: { Accept: 'text/html' }, credentials: 'same-origin', signal: controller.signal })
        .then((response) => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.text();
        })
        .then((html) => {
            const fragment = new DOMParser().parseFromString(html, 'text/html');
            const newDot = fragment.querySelector('#platau-status-dot');
            const newSrLabel = fragment.querySelector('#platau-status-sr');
            const newPanel = fragment.querySelector('.platau-status-panel');
            if (!newDot || !newSrLabel || !newPanel) {
                throw new Error("Fragment Plat'AU invalide");
            }

            dot.className = newDot.className;
            root.querySelector('.platau-status-panel').replaceWith(newPanel);
            srLabel.textContent = newSrLabel.textContent;
            delete root.dataset.url;
        })
        .catch(showUnknown)
        .finally(() => window.clearTimeout(timeoutId));
}
