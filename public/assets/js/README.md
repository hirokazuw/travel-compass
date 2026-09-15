# Search page modules

`../app.js` loads these native ES modules, then calls their initializers in order.
No package install, bundler or generated bundle is needed for production.

| Module | Entry | DOM contract / responsibility |
| --- | --- | --- |
| tabs | `initTabs` | `.search-tab`, `.tab-panel`, `.hotel-provider-tab`; flight trip type and return-date state |
| flight-suggestions | `initFlightSuggestions` | `[data-flight-city]`; city suggestions, keyboard selection, hidden IATA codes |
| hotel-suggestions | `initHotelSuggestions` | `.hotel-search-form`; debounce, cache, keyboard selection, hidden place metadata |
| results | `initResults` | `.flight-offers-toggle`, `.overseas-hotels-toggle`; extra result visibility |
| ferry-search | `initFerrySearch` | `.ferry-search-form`; company suggestions and route selection |
| ferry-map | `initFerryMap` | `[data-ferry-map]`, `[data-ferry-mode]`; loading, region/port selection, direction reversal and drawing |
| images | `initImages` | `[data-hotel-image]`, `[data-airline-logo]`; image fallbacks |
| history | `initHistory` | `.recent-search-card`; form restoration and tab activation |
| loading | `initLoading` | search forms, `[data-search-loading]`; submit protection, overlay, `pageshow` restoration |

Each initializer accepts a document and remembers initialized documents in a
module-local WeakSet. This supports one initialization per page, not dynamic
replacement of forms. Tab handlers must be ready before history buttons can
activate tabs. Ferry map loaders and mode buttons share a module-local WeakMap;
no function is stored on a DOM element.

Loading receives whether `pageshow` has already occurred while modules were being
fetched. It restores completion state immediately in that case, and also listens
for future `pageshow` events (including browser back/forward restoration).
The entry emits `travelcompass:ready` after all initializers finish.

PHP `ScriptAssetVersion` hashes the entry and all module `.js` files. The entry
passes its version query to each import. Deploy `app.js` and the entire `js/`
directory together; serve `.js` as JavaScript on the same origin.

Run `node tests/browser.mjs` from the repository root for real headless Chrome DOM
contracts. Tests use PHP-rendered production partials and fake API responses.
