const initialized = new WeakSet();

export function initHistory(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
    document.querySelectorAll('.recent-search-card').forEach((card) => {
        card.addEventListener('click', () => {
            if (card.dataset.searchType === 'hotel') {
                const adults = Math.max(1, Math.min(9, Number.parseInt(card.dataset.adults || '1', 10) || 1));
                const children = Math.max(0, Math.min(9, Number.parseInt(card.dataset.children || '0', 10) || 0));
                document.querySelectorAll('.hotel-search-form').forEach((hotelForm) => {
                    hotelForm.elements.hotel_destination.value = card.dataset.destination || '';
                    hotelForm.elements.check_in_date.value = card.dataset.checkIn || '';
                    hotelForm.elements.check_out_date.value = card.dataset.checkOut || '';
                    hotelForm.elements.hotel_adults.value = String(adults);
                    hotelForm.elements.hotel_children.value = String(children);
                });
                document.getElementById('hotel-tab')?.click();
                document.querySelector('.hotel-provider-panel:not([hidden]) .hotel-search-form')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
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
}
