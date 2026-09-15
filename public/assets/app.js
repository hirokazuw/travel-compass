// All feature imports use the same content version as this entry point.
let pageAlreadyShown = document.readyState === 'complete';
window.addEventListener('pageshow', () => { pageAlreadyShown = true; }, { once: true });
const version = new URL(import.meta.url).search;
const features = [
    ['tabs', 'initTabs'],
    ['flight-suggestions', 'initFlightSuggestions'],
    ['hotel-suggestions', 'initHotelSuggestions'],
    ['results', 'initResults'],
    ['ferry-search', 'initFerrySearch'],
    ['ferry-map', 'initFerryMap'],
    ['images', 'initImages'],
    ['history', 'initHistory'],
    ['loading', 'initLoading'],
];
const modules = await Promise.all(features.map(([file]) => import(`./js/${file}.js${version}`)));
features.forEach(([, initialize], index) => modules[index][initialize](document, pageAlreadyShown));
window.dispatchEvent(new Event('travelcompass:ready'));
