document.querySelectorAll('.search-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.search-tab').forEach((item) => {
            const active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
            item.tabIndex = active ? 0 : -1;
        });
        document.querySelectorAll('.tab-panel').forEach((panel) => {
            panel.hidden = panel.id !== tab.dataset.tab;
        });
        document.querySelectorAll('[data-flight-tab-content]').forEach((content) => {
            content.hidden = tab.dataset.tab !== 'flight-panel';
        });
    });
});

document.querySelectorAll('.hotel-provider-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.hotel-provider-tab').forEach((item) => {
            const active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
            item.tabIndex = active ? 0 : -1;
        });
        document.querySelectorAll('.hotel-provider-panel').forEach((panel) => {
            panel.hidden = panel.id !== tab.dataset.providerPanel;
        });
        const provider = tab.dataset.resultProvider || tab.dataset.providerPanel.replace('-hotel-panel', '');
        document.querySelectorAll('[data-provider-results]').forEach((section) => {
            section.hidden = section.dataset.providerResults !== provider;
        });
    });
});

const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
const returnDateInput = document.querySelector('input[name="return_date"]');
function updateReturnDate() {
    if (!returnDateInput) return;
    const oneWay = document.querySelector('input[name="trip_type"]:checked')?.value === 'oneway';
    if (oneWay) returnDateInput.value = '';
    returnDateInput.disabled = oneWay;
    returnDateInput.required = !oneWay;
}
tripTypeInputs.forEach((input) => input.addEventListener('change', updateReturnDate));
updateReturnDate();

document.querySelectorAll('.flight-offers-toggle').forEach((button) => {
    const extraOffers = document.querySelectorAll('[data-extra-offer]');
    button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        extraOffers.forEach((offer) => { offer.hidden = expanded; });
        button.setAttribute('aria-expanded', String(!expanded));
        button.textContent = expanded ? 'もっと見る' : '閉じる';
    });
});

document.querySelectorAll('.rakuten-results-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const hiddenHotels = [...document.querySelectorAll('[data-extra-rakuten-hotel][hidden]')];
        hiddenHotels.slice(0, Number(button.dataset.step) || 5).forEach((hotel) => { hotel.hidden = false; });
        if (!document.querySelector('[data-extra-rakuten-hotel][hidden]')) button.hidden = true;
    });
});

document.querySelectorAll('.recent-search-card').forEach((card) => {
    card.addEventListener('click', () => {
        const flightForm = document.querySelector('.flight-search-form');
        if (!flightForm) return;
        flightForm.elements.origin.value = card.dataset.origin || '';
        flightForm.elements.destination.value = card.dataset.destination || '';
        flightForm.elements.departure_date.value = card.dataset.departureDate || '';
        flightForm.elements.return_date.value = card.dataset.returnDate || '';
        flightForm.elements.travelers.value = card.dataset.travelers || '1';
        const tripType = card.dataset.returnDate ? 'roundtrip' : 'oneway';
        const tripTypeInput = flightForm.querySelector(`input[name="trip_type"][value="${tripType}"]`);
        if (tripTypeInput) tripTypeInput.checked = true;
        updateReturnDate();
        document.getElementById('flight-tab')?.click();
        flightForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});
