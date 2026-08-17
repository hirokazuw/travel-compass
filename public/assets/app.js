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

const hotelResults = document.querySelector('.hotel-results');
const hotelSort = document.querySelector('#hotel-sort');
const hotelMoreButton = document.querySelector('.hotel-results-toggle');
let visibleHotelCount = 5;

function sortedHotelCards() {
    const cards = [...document.querySelectorAll('.hotel-summary-card')];
    const mode = hotelSort?.value || 'recommended';
    cards.sort((a, b) => {
        if (mode === 'rating') return (Number(b.dataset.rating) || -1) - (Number(a.dataset.rating) || -1);
        if (mode === 'price') {
            const aPrice = a.dataset.price === '' ? Number.POSITIVE_INFINITY : Number(a.dataset.price);
            const bPrice = b.dataset.price === '' ? Number.POSITIVE_INFINITY : Number(b.dataset.price);
            return aPrice - bPrice;
        }
        return Number(a.dataset.recommended) - Number(b.dataset.recommended);
    });
    cards.forEach((card) => hotelResults?.append(card));
    return cards;
}

function renderHotelCards() {
    const cards = sortedHotelCards();
    cards.forEach((card, index) => { card.hidden = index >= visibleHotelCount; });
    if (hotelMoreButton) hotelMoreButton.hidden = visibleHotelCount >= cards.length;
}

hotelSort?.addEventListener('change', () => {
    visibleHotelCount = 5;
    renderHotelCards();
});

hotelMoreButton?.addEventListener('click', () => {
    visibleHotelCount += Number(hotelMoreButton.dataset.step) || 5;
    renderHotelCards();
});

async function loadHotelDetails(card) {
    if (!card.hasAttribute('data-detail-pending') || !card.dataset.propertyToken) return;
    const results = card.closest('.hotel-results');
    const params = new URLSearchParams({
        search_type: 'hotel_details',
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        property_token: card.dataset.propertyToken,
        destination: results.dataset.hotelDestination,
        check_in_date: results.dataset.checkIn,
        check_out_date: results.dataset.checkOut,
        adults: results.dataset.adults,
        children: results.dataset.children,
    });

    card.classList.add('is-loading-price');
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: params.toString(),
        });
        if (!response.ok) throw new Error('Hotel details request failed');
        const data = await response.json();
        if (Array.isArray(data.offers) && data.offers.length) {
            card.removeAttribute('data-detail-pending');
            updateHotelOffers(card, data.offers);
        }
    } catch (error) {
        console.warn(error);
    } finally {
        card.classList.remove('is-loading-price');
    }
}

document.querySelectorAll('[data-load-hotel-price]').forEach((button) => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = '料金を検索中…';
        await loadHotelDetails(button.closest('.hotel-summary-card'));
        if (button.isConnected) {
            button.disabled = false;
            button.textContent = '再試行';
        }
    });
});

function updateHotelOffers(card, offers) {
    const best = offers[0];
    const panel = card.querySelector('[data-offer-panel]');
    panel.replaceChildren();

    const bestBox = document.createElement('div');
    bestBox.className = 'hotel-best-offer';
    const title = document.createElement('span');
    title.className = 'hotel-reference-title';
    title.textContent = '参考最安値';
    const site = document.createElement('b');
    site.textContent = best.site;
    bestBox.append(title, site);
    if (best.free_cancellation) {
        const cancellation = document.createElement('span');
        cancellation.className = 'hotel-cancellation';
        cancellation.textContent = '✓ キャンセル無料';
        bestBox.append(cancellation);
    }
    const action = document.createElement('div');
    action.className = 'hotel-price-action';
    const priceBlock = document.createElement('div');
    const nightly = document.createElement('strong');
    nightly.textContent = `¥${Number(best.nightly).toLocaleString('ja-JP')} / 泊`;
    const total = document.createElement('small');
    total.textContent = `${Number(card.dataset.nights) || 1}泊参考 ¥${Number(best.total).toLocaleString('ja-JP')}`;
    priceBlock.append(nightly, total);
    const link = document.createElement('a');
    link.className = 'hotel-compare-button';
    link.href = best.url;
    link.target = '_blank';
    link.rel = 'sponsored noopener';
    link.textContent = '料金プランを見る ›';
    action.append(priceBlock, link);
    const note = document.createElement('small');
    note.className = 'google-price-note';
    note.textContent = 'Google Hotels掲載価格より';
    bestBox.append(action, note);
    panel.append(bestBox);

    const otherPrices = document.createElement('div');
    otherPrices.className = 'hotel-compare-prices';
    const otherTitle = document.createElement('small');
    otherTitle.textContent = 'その他の掲載価格';
    otherPrices.append(otherTitle);
    offers.slice(1, 3).forEach((offer) => {
        const row = document.createElement('span');
        const source = document.createElement('b');
        source.textContent = offer.site;
        row.append(source, document.createTextNode(` ¥${Number(offer.nightly).toLocaleString('ja-JP')} / 泊`));
        otherPrices.append(row);
    });
    if (offers.length > 1) panel.append(otherPrices);
    card.dataset.price = best.nightly;
}

if (hotelResults) renderHotelCards();

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
