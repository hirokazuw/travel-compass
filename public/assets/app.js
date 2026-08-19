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

document.querySelectorAll('.hotel-search-form').forEach((form) => {
    const input = form.querySelector('input[name="hotel_destination"]');
    const suggestions = form.querySelector('.hotel-place-suggestions');
    const status = form.querySelector('.hotel-place-status');
    const loading = form.querySelector('.hotel-place-loading');
    if (!input || !suggestions || !status || !loading) return;

    const cache = new Map();
    let debounceTimer;
    let requestController;
    let requestNumber = 0;
    let activeIndex = -1;

    const clearPlaceMetadata = () => {
        ['hotel_place_id', 'hotel_place_address', 'hotel_place_latitude', 'hotel_place_longitude', 'hotel_place_country'].forEach((name) => {
            if (form.elements[name]) form.elements[name].value = '';
        });
    };

    const closeSuggestions = () => {
        suggestions.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    };

    const selectSuggestion = (item) => {
        input.value = item.name || input.value;
        form.elements.hotel_place_id.value = item.place_id || '';
        form.elements.hotel_place_address.value = item.address || '';
        form.elements.hotel_place_latitude.value = item.latitude ?? '';
        form.elements.hotel_place_longitude.value = item.longitude ?? '';
        form.elements.hotel_place_country.value = item.country_code || '';
        closeSuggestions();
        status.textContent = `${input.value}を選択しました。`;
        input.focus();
    };

    const renderSuggestions = (items, message = '') => {
        suggestions.replaceChildren();
        activeIndex = -1;
        items.slice(0, 8).forEach((item) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            const icon = document.createElement('span');
            icon.className = 'hotel-place-icon';
            icon.textContent = /airport|空港/i.test(`${item.category} ${item.name}`) ? '✈' : /hotel|ホテル/i.test(`${item.category} ${item.name}`) ? '▣' : '📍';
            const copy = document.createElement('span');
            const name = document.createElement('strong');
            name.textContent = item.name || '';
            const detail = document.createElement('small');
            detail.textContent = [item.category, item.address].filter(Boolean).join('・');
            copy.append(name, detail);
            option.append(icon, copy);
            option.addEventListener('click', () => selectSuggestion(item));
            suggestions.append(option);
        });
        suggestions.hidden = items.length === 0;
        input.setAttribute('aria-expanded', String(items.length > 0));
        status.textContent = message || (items.length === 0 ? '候補が見つかりませんでした。手入力で検索できます。' : '');
    };

    const fetchSuggestions = async (query) => {
        const normalizedQuery = query.toLocaleLowerCase();
        if (cache.has(normalizedQuery)) {
            renderSuggestions(cache.get(normalizedQuery));
            return;
        }
        requestController?.abort();
        requestController = new AbortController();
        const currentRequest = ++requestNumber;
        loading.hidden = false;
        status.textContent = '候補を検索しています…';
        const body = new FormData();
        body.set('search_type', 'hotel_destination_suggestions');
        body.set('csrf', form.elements.csrf.value);
        body.set('query', query);
        try {
            const response = await fetch(window.location.href, { method: 'POST', body, credentials: 'same-origin', signal: requestController.signal });
            const payload = await response.json();
            if (currentRequest !== requestNumber || input.value.trim() !== query) return;
            const items = Array.isArray(payload.suggestions) ? payload.suggestions : [];
            cache.set(normalizedQuery, items);
            renderSuggestions(items, payload.message || '');
        } catch (error) {
            if (error.name === 'AbortError') return;
            closeSuggestions();
            status.textContent = '候補を取得できませんでした。手入力で検索できます。';
        } finally {
            if (currentRequest === requestNumber) loading.hidden = true;
        }
    };

    input.addEventListener('input', () => {
        clearPlaceMetadata();
        clearTimeout(debounceTimer);
        requestController?.abort();
        requestNumber++;
        loading.hidden = true;
        closeSuggestions();
        suggestions.replaceChildren();
        const query = input.value.trim();
        if (query.length < 2) {
            status.textContent = '';
            return;
        }
        status.textContent = '入力が終わると候補を検索します…';
        debounceTimer = setTimeout(() => fetchSuggestions(query), 400);
    });

    input.addEventListener('keydown', (event) => {
        const options = [...suggestions.querySelectorAll('[role="option"]')];
        if (suggestions.hidden || options.length === 0) {
            if (event.key === 'Escape') closeSuggestions();
            return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = event.key === 'ArrowDown'
                ? (activeIndex + 1) % options.length
                : (activeIndex - 1 + options.length) % options.length;
            options.forEach((option, index) => option.setAttribute('aria-selected', String(index === activeIndex)));
            options[activeIndex].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            options[activeIndex].click();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeSuggestions();
        }
    });

    document.addEventListener('click', (event) => {
        if (!form.querySelector('.hotel-destination-field').contains(event.target)) closeSuggestions();
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
