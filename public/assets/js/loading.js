const initialized = new WeakSet();

export function initLoading(document = globalThis.document, pageAlreadyShown = false) {
    if (initialized.has(document)) return;
    initialized.add(document);
    const searchLoading = document.querySelector('[data-search-loading]');
    let searchLoadingTimer;

    const closeSearchLoading = () => {
        clearInterval(searchLoadingTimer);
        if (searchLoading) searchLoading.hidden = true;
        document.body.removeAttribute('aria-busy');
        document.documentElement.classList.remove('is-search-loading');
        document.querySelectorAll('[data-loading-submit]').forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-disabled');
            button.removeAttribute('data-loading-submit');
        });
        document.querySelectorAll('[data-loading-generated]').forEach((input) => input.remove());
    };

    const showSearchLoading = (form, type) => {
        if (!searchLoading) return;
        const isFlight = type === 'flight';
        const isFerry = type === 'ferry';
        const value = (name) => form.elements[name]?.value?.trim() || '';
        const departure = isFlight ? value('departure_date') : (isFerry ? '' : value('check_in_date'));
        const arrival = isFlight ? value('return_date') : (isFerry ? '' : value('check_out_date'));

        searchLoading.querySelector('[data-search-loading-kind]').textContent = isFlight ? 'FLIGHT SEARCH' : (isFerry ? 'FERRY SEARCH' : 'HOTEL SEARCH');
        searchLoading.querySelector('[data-search-loading-title]').textContent = isFlight ? '航空券を検索しています…' : (isFerry ? 'フェリー航路を検索しています…' : 'ホテルを検索しています…');
        searchLoading.querySelector('[data-search-loading-detail]').textContent = isFlight ? 'フライト情報を取得中です' : (isFerry ? '登録航路を検索中です' : '宿泊施設の情報を取得中です');
        searchLoading.querySelector('[data-search-loading-route-label]').textContent = isFerry ? '航路' : (isFlight ? '区間' : '目的地');
        const ferryRoute = isFerry ? (form.elements.ferry_route_label?.value || form.elements.ferry_route_id?.selectedOptions?.[0]?.textContent || '') : '';
        searchLoading.querySelector('[data-search-loading-route]').textContent = isFerry
            ? `${value('ferry_company_name')}・${ferryRoute}`
            : (isFlight ? `${value('origin')} → ${value('destination')}` : value('hotel_destination'));
        searchLoading.querySelector('[data-search-loading-dates]').textContent = arrival ? `${departure} → ${arrival}` : departure;
        searchLoading.querySelector('[data-search-loading-travelers]').textContent = isFerry ? '' : (isFlight
            ? `${value('travelers')}名`
            : `大人${value('hotel_adults')}名・子供${value('hotel_children')}名`);
        searchLoading.querySelector('[data-search-loading-dates-row]').hidden = isFerry;
        searchLoading.querySelector('[data-search-loading-travelers-row]').hidden = isFerry;

        try {
            sessionStorage.setItem('travelCompassSearchLoading', JSON.stringify({
                kind: searchLoading.querySelector('[data-search-loading-kind]').textContent,
                title: searchLoading.querySelector('[data-search-loading-title]').textContent,
                detail: searchLoading.querySelector('[data-search-loading-detail]').textContent,
                routeLabel: searchLoading.querySelector('[data-search-loading-route-label]').textContent,
                route: searchLoading.querySelector('[data-search-loading-route]').textContent,
                dates: searchLoading.querySelector('[data-search-loading-dates]').textContent,
                travelers: searchLoading.querySelector('[data-search-loading-travelers]').textContent,
            }));
        } catch (error) {
            // Storage may be unavailable in privacy-restricted browsing contexts.
        }

        const progress = searchLoading.querySelector('[data-search-loading-progress]');
        let progressValue = 3;
        progress.style.width = `${progressValue}%`;
        searchLoading.hidden = false;
        document.body.setAttribute('aria-busy', 'true');
        document.documentElement.classList.add('is-search-loading');
        searchLoading.querySelector('.search-loading-card')?.focus({ preventScroll: true });

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!reducedMotion) {
            searchLoadingTimer = window.setInterval(() => {
                progressValue = Math.min(88, progressValue + Math.max(0.35, (88 - progressValue) * 0.035));
                progress.style.width = `${progressValue}%`;
                if (progressValue >= 88) clearInterval(searchLoadingTimer);
            }, 450);
        }
    };

    document.querySelectorAll('.flight-search-form, .hotel-search-form, .ferry-search-form, .ferry-map-route-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
            const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
            if (submitter?.name) {
                const submittedValue = document.createElement('input');
                submittedValue.type = 'hidden';
                submittedValue.name = submitter.name;
                submittedValue.value = submitter.value;
                submittedValue.dataset.loadingGenerated = '';
                form.append(submittedValue);
            }
            if (submitter) {
                submitter.disabled = true;
                submitter.setAttribute('aria-disabled', 'true');
                submitter.dataset.loadingSubmit = '';
            }
            const type = form.classList.contains('flight-search-form') ? 'flight'
                : (form.classList.contains('ferry-search-form') || form.classList.contains('ferry-map-route-form') ? 'ferry' : 'hotel');
            showSearchLoading(form, type);
        });
    });

    const onPageShow = () => {
        document.querySelectorAll('.flight-search-form, .hotel-search-form, .ferry-search-form, .ferry-map-route-form').forEach((form) => delete form.dataset.submitting);
        closeSearchLoading();
        let previousSearch = null;
        try {
            previousSearch = JSON.parse(sessionStorage.getItem('travelCompassSearchLoading'));
            sessionStorage.removeItem('travelCompassSearchLoading');
        } catch (error) {
            previousSearch = null;
        }
        if (document.body.dataset.searchOutcome !== 'success' || !previousSearch || !searchLoading) return;

        const content = {
            kind: '[data-search-loading-kind]',
            title: '[data-search-loading-title]',
            detail: '[data-search-loading-detail]',
            routeLabel: '[data-search-loading-route-label]',
            route: '[data-search-loading-route]',
            dates: '[data-search-loading-dates]',
            travelers: '[data-search-loading-travelers]',
        };
        Object.entries(content).forEach(([key, selector]) => {
            searchLoading.querySelector(selector).textContent = previousSearch[key] || '';
        });
        const wasFerrySearch = previousSearch.kind === 'FERRY SEARCH';
        searchLoading.querySelector('[data-search-loading-dates-row]').hidden = wasFerrySearch;
        searchLoading.querySelector('[data-search-loading-travelers-row]').hidden = wasFerrySearch;
        searchLoading.querySelector('[data-search-loading-progress]').style.width = '100%';
        searchLoading.hidden = false;
        document.body.setAttribute('aria-busy', 'true');
        document.documentElement.classList.add('is-search-loading');
        const completionDelay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 180 : 600;
        window.setTimeout(closeSearchLoading, completionDelay);
    };
    window.addEventListener('pageshow', onPageShow);
    if (pageAlreadyShown) onPageShow();
}
