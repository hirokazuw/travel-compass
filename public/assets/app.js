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
        document.querySelectorAll('[data-flight-history]').forEach((history) => {
            history.hidden = tab.dataset.tab !== 'flight-panel';
        });
        if (tab.dataset.tab !== 'hotel-panel') {
            document.querySelectorAll('[data-provider-results]').forEach((section) => {
                section.hidden = true;
            });
        } else {
            document.querySelector('.hotel-provider-tab.is-active')?.click();
        }
    });
});

document.querySelectorAll('.flight-search-form').forEach((form) => {
    const returnDateInput = form.querySelector('input[name="return_date"]');
    const updateReturnDate = () => {
        if (!returnDateInput) return;
        const oneWay = form.querySelector('input[name="trip_type"]:checked')?.value === 'oneway';
        if (oneWay) returnDateInput.value = '';
        returnDateInput.disabled = oneWay;
        returnDateInput.required = !oneWay;
    };
    form.querySelectorAll('input[name="trip_type"]').forEach((input) => input.addEventListener('change', updateReturnDate));
    updateReturnDate();
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
        const scope = tab.id === 'overseas-hotel-tab' ? 'overseas' : 'domestic';
        document.querySelectorAll('[data-hotel-result-scope]').forEach((section) => {
            section.hidden = section.dataset.hotelResultScope !== scope;
        });
    });
});

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

document.querySelectorAll('.overseas-hotels-toggle').forEach((button) => {
    const extraHotels = document.querySelectorAll('[data-extra-overseas-hotel]');
    button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        extraHotels.forEach((hotel) => { hotel.hidden = expanded; });
        button.setAttribute('aria-expanded', String(!expanded));
        button.textContent = expanded ? 'もっと見る' : '閉じる';
    });
});

document.querySelectorAll('[data-hotel-image]').forEach((image) => {
    let fallbacks = [];
    try { fallbacks = JSON.parse(image.dataset.fallbackImages || '[]'); } catch (_) {}
    image.addEventListener('error', () => {
        const next = fallbacks.shift();
        if (next) {
            image.src = next;
            return;
        }
        image.hidden = true;
        const placeholder = image.parentElement?.querySelector('.overseas-hotel-placeholder');
        if (placeholder) placeholder.hidden = false;
    });
});

document.querySelectorAll('[data-airline-logo]').forEach((logo) => {
    logo.addEventListener('error', () => {
        logo.hidden = true;
        const fallback = logo.parentElement?.querySelector('[data-airline-logo-fallback]');
        if (fallback) fallback.hidden = false;
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
        tripTypeInput?.dispatchEvent(new Event('change'));
        document.getElementById('flight-tab')?.click();
        flightForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});
