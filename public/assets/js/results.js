const initialized = new WeakSet();

export function initResults(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
    document.querySelectorAll('.flight-offers-toggle').forEach((button) => {
        const extraOffers = document.querySelectorAll('[data-extra-offer]');
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            extraOffers.forEach((offer) => { offer.hidden = expanded; });
            button.setAttribute('aria-expanded', String(!expanded));
            button.textContent = expanded ? 'もっと見る' : '閉じる';
        });
    });

    document.querySelectorAll('.overseas-hotels-toggle').forEach((button) => {
        const extraHotels = document.querySelectorAll('[data-extra-overseas-hotel]');
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            extraHotels.forEach((hotel) => { hotel.hidden = expanded; });
            button.setAttribute('aria-expanded', String(!expanded));
            button.textContent = expanded ? 'もっと見る' : '閉じる';
        });
    });
}
