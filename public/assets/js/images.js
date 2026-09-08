const initialized = new WeakSet();

export function initImages(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
    document.querySelectorAll('[data-hotel-image]').forEach((image) => {
        let fallbacks = [];
        try { fallbacks = JSON.parse(image.dataset.fallbackImages || '[]'); } catch (_) {}
        const onError = () => {
            const next = fallbacks.shift();
            if (next) {
                image.src = next;
                return;
            }
            image.hidden = true;
            const placeholder = image.parentElement?.querySelector('.overseas-hotel-placeholder');
            if (placeholder) placeholder.hidden = false;
        };
        image.addEventListener('error', onError);
        // Module loading may finish after an initial image request has already failed.
        if (image.complete && image.naturalWidth === 0) onError();
    });

    document.querySelectorAll('[data-airline-logo]').forEach((logo) => {
        const onError = () => {
            logo.hidden = true;
            const fallback = logo.parentElement?.querySelector('[data-airline-logo-fallback]');
            if (fallback) fallback.hidden = false;
        };
        logo.addEventListener('error', onError);
        if (logo.complete && logo.naturalWidth === 0) onError();
    });
}
