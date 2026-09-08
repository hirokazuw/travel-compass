// Runs in a real browser against PHP-rendered production partials. All API calls are fake.
const ready = new Promise((resolve) => {
    window.addEventListener('travelcompass:ready', resolve, { once: true });
    window.addEventListener('load', () => {
        if (!document.querySelector('script[type="module"][src*="app.js"]')) resolve();
    }, { once: true });
});
const requests = [];
const route = {
    id: 2, company_id: 1, company_name: 'Fixture Ferry', duration: '10時間',
    vehicle_available: true, overnight: true, fare_from: '12,000', fare_currency: 'JPY',
    fare_updated: '料金確認日：2026/08/22', destination_url: 'https://route.example/',
    departure: { name: '東京港', region: 'kanto', x: 60, y: 62 },
    arrival: { name: '徳島港', region: 'shikoku', x: 39, y: 76 }, label: '東京港 → 徳島港',
};
window.fetch = async (_url, options) => {
    const data = Object.fromEntries(options.body);
    requests.push(data);
    if (data.csrf !== 'browser-token') throw new Error('Missing CSRF');
    const payload = {
        hotel_destination_suggestions: { suggestions: [{ name: 'Tokyo Hotel', place_id: 'place-1', address: 'Tokyo', latitude: 35, longitude: 139, country_code: 'JP' }] },
        ferry_company_suggestions: { suggestions: [{ id: 1, name: 'Fixture Ferry' }] },
        ferry_company_routes: { routes: [{ id: 2, label: '東京港 → 徳島港' }] },
        ferry_map_data: { routes: [route] },
    }[data.search_type];
    if (!payload) throw new Error('Unexpected endpoint');
    return { ok: true, json: async () => payload };
};
window.addEventListener('error', (event) => { if (event.message) window.browserFailure = event.message; });
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const check = (value, message) => { if (!value) throw new Error(message); };
const $ = (selector) => document.querySelector(selector);
const input = (element, value) => { element.value = value; element.dispatchEvent(new Event('input', { bubbles: true })); };
const key = (element, name) => element.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }));
ready.then(async () => {
    const passed = [];
    try {
        $('#hotel-tab').click();
        check(!$('#hotel-panel').hidden && $('#flight-panel').hidden, 'main tab panels');
        check($('#hotel-tab').getAttribute('aria-selected') === 'true', 'tab ARIA');
        $('[data-hotel-scope="overseas"]').click();
        check(!$('[data-hotel-result-scope="overseas"]').hidden, 'hotel scope results');
        passed.push('tabs');

        const flight = $('.flight-search-form');
        flight.querySelector('[value="oneway"]').click();
        check(flight.elements.return_date.disabled && !flight.elements.return_date.required, 'oneway return date');
        flight.querySelector('[value="roundtrip"]').click();
        check(!flight.elements.return_date.disabled && flight.elements.return_date.required, 'roundtrip return date');
        $('.recent-search-card[data-search-type="flight"]').click();
        check(flight.elements.origin.value === '東京' && flight.elements.travelers.value === '3' && flight.elements.return_date.disabled, 'flight history');
        $('.recent-search-card[data-search-type="hotel"]').click();
        check($('.hotel-search-form').elements.hotel_destination.value === '札幌', 'hotel history');
        passed.push('trip and history');

        const hotel = $('.hotel-provider-panel:not([hidden]) .hotel-search-form');
        input(hotel.elements.hotel_destination, 'Tokyo');
        await sleep(500);
        check(!hotel.querySelector('.hotel-place-suggestions').hidden, 'hotel suggestions');
        key(hotel.elements.hotel_destination, 'ArrowDown');
        key(hotel.elements.hotel_destination, 'Enter');
        check(hotel.elements.hotel_place_id.value === 'place-1', 'hotel selection metadata');
        const calls = requests.length;
        input(hotel.elements.hotel_destination, 'Tokyo');
        await sleep(500);
        check(requests.length === calls, 'hotel suggestion cache');
        key(hotel.elements.hotel_destination, 'Escape');
        check(hotel.querySelector('.hotel-place-suggestions').hidden, 'escape suggestions');
        input(hotel.elements.hotel_destination, 'T');
        check(hotel.elements.hotel_place_id.value === '', 'clear metadata');
        passed.push('hotel suggestions');

        $('#ferry-tab').click();
        const ferry = $('.ferry-search-form');
        input(ferry.elements.ferry_company_name, 'Fixture');
        await sleep(350);
        key(ferry.elements.ferry_company_name, 'ArrowDown');
        key(ferry.elements.ferry_company_name, 'Enter');
        await sleep(0);
        check(ferry.elements.ferry_company_id.value === '1' && !ferry.elements.ferry_route_id.disabled, 'ferry company routes');
        check(ferry.elements.ferry_route_id.options[1].value === '2', 'route id');
        passed.push('ferry suggestions');

        $('[data-ferry-mode="map"]').click();
        await sleep(0);
        $('[data-region="shikoku"]').click();
        $('[data-ferry-map-selection-list] button').click();
        $('[data-ferry-map-selection-list] button').click();
        check($('.ferry-port-pin.is-departure').dataset.portName === '徳島港', 'reverse departure');
        check($('.ferry-port-pin.is-arrival').dataset.portName === '東京港', 'reverse arrival');
        check($('.ferry-map-route-lines path'), 'route line');
        check($('[data-ferry-map-routes]').textContent.includes('運賃・ダイヤ・運航状況は参考情報です。最新情報・空席状況は各フェリー会社公式サイトでご確認ください。'), 'map freshness disclaimer');
        check($('[data-ferry-map-route-list]').textContent.includes('料金確認日：2026/08/22'), 'map fare confirmation');
        $('.ferry-map-canvas').click();
        check(!$('[data-ferry-map] .ferry-map-layout').classList.contains('has-selection'), 'map reset');
        $('[data-ferry-mode="conditions"]').click();
        $('[data-ferry-mode="map"]').click();
        check(requests.filter((r) => r.search_type === 'ferry_map_data').length === 1, 'map loads once');
        passed.push('ferry map');

        $('.flight-offers-toggle').click();
        check(!$('[data-extra-offer]').hidden, 'extra offers');
        $('.flight-offers-toggle').click();
        check($('[data-extra-offer]').hidden, 'collapse offers');
        $('.overseas-hotels-toggle').click();
        check(!$('[data-extra-overseas-hotel]').hidden, 'extra hotels');
        const image = $('[data-hotel-image]');
        image.dispatchEvent(new Event('error'));
        image.dispatchEvent(new Event('error'));
        check(image.hidden && !image.parentElement.querySelector('.overseas-hotel-placeholder').hidden, 'hotel image fallback');
        const logo = $('[data-airline-logo]');
        logo.dispatchEvent(new Event('error'));
        check(logo.hidden && !logo.parentElement.querySelector('[data-airline-logo-fallback]').hidden, 'airline fallback');
        passed.push('results and images');

        const submitter = flight.querySelector('button[type="submit"], button:not([type])');
        flight.addEventListener('submit', (event) => event.preventDefault());
        flight.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter }));
        check(!$('[data-search-loading]').hidden && submitter.disabled, 'loading overlay');
        check(new FormData(flight).get('search_type') === 'flight', 'search type retained');
        flight.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter }));
        check(flight.querySelectorAll('[data-loading-generated]').length === (submitter.name ? 1 : 0), 'duplicate submit guard');
        window.dispatchEvent(new PageTransitionEvent('pageshow'));
        check($('[data-search-loading]').hidden && !submitter.disabled && !flight.dataset.submitting, 'pageshow reset');
        const hotelSubmitter = hotel.querySelector('button[name="search_type"]');
        hotel.addEventListener('submit', (event) => event.preventDefault());
        hotel.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: hotelSubmitter }));
        check(hotel.querySelector('[data-loading-generated]').value === 'hotel', 'named submitter retained');
        window.dispatchEvent(new PageTransitionEvent('pageshow'));
        check(!hotel.querySelector('[data-loading-generated]'), 'generated input removed');
        document.body.dataset.searchOutcome = 'success';
        sessionStorage.setItem('travelCompassSearchLoading', JSON.stringify({ kind: 'FERRY SEARCH', route: 'Fixture route' }));
        window.dispatchEvent(new PageTransitionEvent('pageshow'));
        check(!$('[data-search-loading]').hidden && $('[data-search-loading-dates-row]').hidden, 'success completion overlay');
        await sleep(700);
        check($('[data-search-loading]').hidden, 'completion closes');
        passed.push('loading');
        check(!window.browserFailure, window.browserFailure);
        document.body.dataset.browserResult = JSON.stringify({ passed });
    } catch (error) {
        document.body.dataset.browserResult = JSON.stringify({ passed, error: error.stack });
    }
});
