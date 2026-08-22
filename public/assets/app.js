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
        document.querySelectorAll('[data-ferry-tab-content]').forEach((content) => {
            content.hidden = tab.dataset.tab !== 'ferry-panel';
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

document.querySelectorAll('.ferry-search-form').forEach((form) => {
    const companyInput = form.elements.ferry_company_name;
    const companyId = form.elements.ferry_company_id;
    const routeSelect = form.elements.ferry_route_id;
    const suggestions = form.querySelector('.ferry-company-suggestions');
    const status = form.querySelector('.ferry-company-status');
    let debounceTimer;
    let requestController;
    let activeIndex = -1;

    const resetRoutes = (message = '先にフェリー会社を選択してください') => {
        routeSelect.replaceChildren(new Option(message, ''));
        routeSelect.disabled = true;
    };
    const closeSuggestions = () => {
        suggestions.hidden = true;
        companyInput.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    };
    const loadRoutes = async (selectedCompanyId) => {
        resetRoutes('航路を読み込んでいます…');
        const body = new FormData();
        body.set('search_type', 'ferry_company_routes');
        body.set('csrf', form.elements.csrf.value);
        body.set('company_id', selectedCompanyId);
        try {
            const response = await fetch(window.location.href, { method: 'POST', body, credentials: 'same-origin' });
            if (!response.ok || companyId.value !== String(selectedCompanyId)) throw new Error('route request failed');
            const payload = await response.json();
            const routes = Array.isArray(payload.routes) ? payload.routes : [];
            routeSelect.replaceChildren(new Option(routes.length ? '航路を選択してください' : '利用可能な航路がありません', ''));
            routes.forEach((route) => routeSelect.add(new Option(route.label || '', String(route.id || ''))));
            routeSelect.disabled = routes.length === 0;
            status.textContent = routes.length ? '' : 'この会社の航路は登録されていません。';
        } catch (_) {
            if (companyId.value !== String(selectedCompanyId)) return;
            resetRoutes('航路を取得できませんでした');
            status.textContent = '航路を取得できませんでした。会社を選び直してください。';
        }
    };
    const selectCompany = (company) => {
        companyInput.value = company.name || '';
        companyId.value = String(company.id || '');
        closeSuggestions();
        status.textContent = '';
        loadRoutes(companyId.value);
        routeSelect.focus();
    };
    const renderSuggestions = (items) => {
        suggestions.replaceChildren();
        activeIndex = -1;
        items.forEach((company) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.textContent = company.name || '';
            option.addEventListener('click', () => selectCompany(company));
            suggestions.append(option);
        });
        suggestions.hidden = items.length === 0;
        companyInput.setAttribute('aria-expanded', String(items.length > 0));
        status.textContent = items.length ? '候補からフェリー会社を選択してください。' : '候補が見つかりませんでした。';
    };
    const fetchCompanies = async (query) => {
        requestController?.abort();
        requestController = new AbortController();
        const body = new FormData();
        body.set('search_type', 'ferry_company_suggestions');
        body.set('csrf', form.elements.csrf.value);
        body.set('query', query);
        try {
            const response = await fetch(window.location.href, { method: 'POST', body, credentials: 'same-origin', signal: requestController.signal });
            if (!response.ok || companyInput.value.trim() !== query) return;
            const payload = await response.json();
            renderSuggestions(Array.isArray(payload.suggestions) ? payload.suggestions : []);
        } catch (error) {
            if (error.name === 'AbortError') return;
            closeSuggestions();
            status.textContent = '会社候補を取得できませんでした。';
        }
    };

    companyInput.addEventListener('input', () => {
        companyId.value = '';
        resetRoutes();
        clearTimeout(debounceTimer);
        requestController?.abort();
        closeSuggestions();
        const query = companyInput.value.trim();
        if (query === '') {
            status.textContent = '';
            return;
        }
        status.textContent = '候補を検索しています…';
        debounceTimer = setTimeout(() => fetchCompanies(query), 250);
    });
    companyInput.addEventListener('keydown', (event) => {
        const options = [...suggestions.querySelectorAll('[role="option"]')];
        if (suggestions.hidden || options.length === 0) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = event.key === 'ArrowDown'
                ? (activeIndex + 1) % options.length
                : (activeIndex - 1 + options.length) % options.length;
            options.forEach((option, index) => option.setAttribute('aria-selected', String(index === activeIndex)));
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            options[activeIndex].click();
        } else if (event.key === 'Escape') {
            closeSuggestions();
        }
    });
    document.addEventListener('click', (event) => {
        if (!form.querySelector('.ferry-company-field').contains(event.target)) closeSuggestions();
    });
});

document.querySelectorAll('[data-ferry-map]').forEach((map) => {
    const pins = map.querySelector('[data-ferry-map-pins]');
    const lines = map.querySelector('.ferry-map-route-lines');
    const routesPanel = map.querySelector('[data-ferry-map-routes]');
    const routeList = map.querySelector('[data-ferry-map-route-list]');
    const status = map.querySelector('[data-ferry-map-status]');
    const heading = map.querySelector('.ferry-map-heading h3');
    const submitForm = map.querySelector('.ferry-map-route-form');
    const mapLayout = map.querySelector('.ferry-map-layout');
    const selectionPanel = map.querySelector('[data-ferry-map-selection]');
    const selectionTitle = map.querySelector('[data-ferry-map-selection-title]');
    const selectionList = map.querySelector('[data-ferry-map-selection-list]');
    let mapRoutes = [];
    let loaded = false;

    const clearMapSelection = () => {
        pins.replaceChildren();
        lines.replaceChildren();
        routeList.replaceChildren();
        selectionList.replaceChildren();
        selectionPanel.hidden = true;
        mapLayout.classList.remove('has-selection');
        routesPanel.hidden = true;
        heading.textContent = '地域を選択してください';
        status.textContent = '地域 → A地点 → B地点の順に選択します。';
        map.querySelector('.ferry-map-canvas').classList.remove('is-detail');
        map.querySelectorAll('.ferry-region').forEach((region) => region.classList.remove('is-selected', 'is-muted'));
    };
    const pinButton = (port, className, onClick = null) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `ferry-port-pin ${className}`;
        button.style.setProperty('--map-x', `${port.x}%`);
        button.style.setProperty('--map-y', `${port.y}%`);
        const marker = document.createElement('span');
        marker.setAttribute('aria-hidden', 'true');
        button.append(marker, document.createTextNode(port.name || ''));
        button.dataset.portName = port.name || '';
        if (onClick) button.addEventListener('click', onClick);
        else button.disabled = true;
        pins.append(button);
    };
    const submitRoute = (route) => {
        submitForm.elements.ferry_company_name.value = route.company_name || '';
        submitForm.elements.ferry_company_id.value = String(route.company_id || '');
        submitForm.elements.ferry_route_id.value = String(route.id || '');
        submitForm.elements.ferry_route_label.value = route.label || '';
        submitForm.requestSubmit();
    };
    const selectionButton = (primary, secondary, onClick, onPreview = null) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ferry-map-selection-choice';
        const label = document.createElement('strong');
        label.textContent = primary;
        button.append(label);
        if (secondary) {
            const detail = document.createElement('span');
            detail.textContent = secondary;
            button.append(detail);
        }
        button.addEventListener('click', onClick);
        if (onPreview) {
            button.addEventListener('mouseenter', onPreview);
            button.addEventListener('focus', onPreview);
        }
        selectionList.append(button);
    };
    const showRoutes = (port, routes) => {
        pins.replaceChildren();
        lines.replaceChildren();
        routeList.replaceChildren();
        pinButton(port, 'is-departure');
        const destinations = new Map();
        routes.forEach((route) => {
            destinations.set(route.arrival.name, route.arrival);
        });
        const positionedDestinations = new Map();
        const occupied = [{ x: port.x, y: port.y }];
        const offsets = [[0, 0], [0, -6], [0, 6], [6, -3], [-6, 3], [8, 5], [-8, -5]];
        destinations.forEach((destination, name) => {
            let positioned = { ...destination };
            for (const [offsetX, offsetY] of offsets) {
                const candidate = {
                    ...destination,
                    x: Math.max(5, Math.min(95, destination.x + offsetX)),
                    y: Math.max(5, Math.min(95, destination.y + offsetY)),
                };
                const overlaps = occupied.some((other) => Math.abs(other.x - candidate.x) < 10 && Math.abs(other.y - candidate.y) < 6);
                if (!overlaps) {
                    positioned = candidate;
                    break;
                }
            }
            occupied.push(positioned);
            positionedDestinations.set(name, positioned);
        });
        let lockedDestination = '';
        const highlightDestination = (name = '') => {
            lines.replaceChildren();
            pins.querySelectorAll('.is-arrival').forEach((pin) => pin.classList.toggle('is-highlighted', pin.dataset.portName === name));
            routeList.querySelectorAll('.ferry-map-route-choice').forEach((choice) => {
                const active = choice.dataset.arrival === name;
                choice.classList.toggle('is-highlighted', active);
                choice.hidden = !active;
            });
            routesPanel.hidden = name === '';
            if (name === '') return;
            const route = routes.find((candidate) => candidate.arrival.name === name);
            const destination = positionedDestinations.get(name);
            if (!route || !destination) return;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const middleX = (route.departure.x + destination.x) / 2;
            const middleY = Math.min(route.departure.y, destination.y) - 8;
            path.setAttribute('d', `M ${route.departure.x} ${route.departure.y} Q ${middleX} ${middleY} ${destination.x} ${destination.y}`);
            path.classList.add('ferry-route-line');
            lines.append(path);
        };
        routes.forEach((route) => {
            const item = document.createElement('article');
            item.className = `ferry-card ferry-map-detail-card${route.destination_url ? ' is-clickable' : ''}`;
            const content = document.createElement(route.destination_url ? 'a' : 'div');
            content.className = 'ferry-card-content';
            if (route.destination_url) {
                content.href = route.destination_url;
                content.target = '_blank';
                content.rel = 'noopener';
                content.setAttribute('aria-label', `${route.company_name || 'フェリー会社'}の公式ページを新しいタブで開く`);
            }
            const main = document.createElement('div');
            main.className = 'ferry-card-main';
            const company = document.createElement('h3');
            company.textContent = route.company_name || '';
            main.append(company);
            if (route.route_name) {
                const routeName = document.createElement('p');
                routeName.className = 'ferry-route-name';
                routeName.textContent = route.route_name;
                main.append(routeName);
            }
            const portsLabel = document.createElement('strong');
            portsLabel.className = 'ferry-ports';
            portsLabel.textContent = route.label || '';
            main.append(portsLabel);
            const details = document.createElement('div');
            details.className = 'ferry-details';
            if (route.duration) {
                const duration = document.createElement('span');
                duration.textContent = `所要時間：${route.duration}`;
                details.append(duration);
            }
            const vehicle = document.createElement('span');
            vehicle.textContent = `車両積載：${route.vehicle_available ? '可' : '不可'}`;
            const overnight = document.createElement('span');
            overnight.textContent = route.overnight ? '夜行便' : '昼行便';
            details.append(vehicle, overnight);
            main.append(details);
            content.append(main);
            if (route.fare_from) {
                const fare = document.createElement('div');
                fare.className = 'ferry-fare';
                const fareLabel = document.createElement('small');
                fareLabel.textContent = '参考運賃';
                const fareAmount = document.createElement('strong');
                fareAmount.textContent = `${route.fare_currency === 'JPY' ? '' : `${route.fare_currency} `}${route.fare_from}${route.fare_currency === 'JPY' ? '円' : ''}〜`;
                fare.append(fareLabel, fareAmount);
                if (route.fare_updated) {
                    const updated = document.createElement('span');
                    updated.textContent = route.fare_updated;
                    fare.append(updated);
                }
                content.append(fare);
            }
            item.append(content);
            item.dataset.arrival = route.arrival.name || '';
            routeList.append(item);
        });
        positionedDestinations.forEach((destination) => {
            pinButton(destination, 'is-arrival');
        });
        heading.textContent = `${port.name}からつながる航路`;
        status.textContent = `${routes.length}件の航路があります。航路を選択してください。`;
        routesPanel.hidden = true;
        lockedDestination = positionedDestinations.keys().next().value || '';
        highlightDestination(lockedDestination);
    };
    const selectRegion = (button) => {
        const regionId = button.dataset.region;
        const regionRoutes = mapRoutes.filter((route) => route.departure.region === regionId || route.arrival.region === regionId);
        const ports = new Map();
        regionRoutes.forEach((route) => {
            if (route.departure.region === regionId) ports.set(route.departure.name, route.departure);
            if (route.arrival.region === regionId) ports.set(route.arrival.name, route.arrival);
        });
        pins.replaceChildren();
        lines.replaceChildren();
        routeList.replaceChildren();
        routesPanel.hidden = true;
        map.querySelector('.ferry-map-canvas').classList.add('is-detail');
        mapLayout.classList.add('has-selection');
        selectionPanel.hidden = false;
        selectionList.replaceChildren();
        map.querySelectorAll('.ferry-region').forEach((region) => {
            region.classList.toggle('is-selected', region === button);
            region.classList.toggle('is-muted', region !== button);
        });
        ports.forEach((port) => {
            const showPort = () => {
                pins.replaceChildren();
                lines.replaceChildren();
                routeList.replaceChildren();
                routesPanel.hidden = true;
                pinButton(port, 'is-departure');
            };
            selectionButton(port.name, '', () => {
            showPort();
            const routes = mapRoutes
                .filter((route) => route.departure.name === port.name || route.arrival.name === port.name)
                .map((route) => {
                    if (route.departure.name === port.name) return route;
                    return {
                        ...route,
                        departure: route.arrival,
                        arrival: route.departure,
                        label: `${route.arrival.name} → ${route.departure.name}`,
                    };
                });
            const destinations = new Map();
            routes.forEach((route) => destinations.set(route.arrival.name, route.arrival));
            selectionTitle.textContent = 'B地点を選択';
            selectionList.replaceChildren();
            destinations.forEach((destination) => {
                const selectedRoutes = routes.filter((route) => route.arrival.name === destination.name);
                const showSelectedRoutes = () => showRoutes(port, selectedRoutes);
                selectionButton(destination.name, `${selectedRoutes.length}航路`, () => {
                    showSelectedRoutes();
                    selectionList.querySelectorAll('.ferry-map-selection-choice').forEach((choice) => choice.classList.toggle('is-selected', choice.querySelector('strong')?.textContent === destination.name));
                }, showSelectedRoutes);
            });
            heading.textContent = `${port.name}と結ぶB地点を選択してください`;
            status.textContent = `${destinations.size}か所の候補があります。`;
            }, showPort);
        });
        selectionTitle.textContent = `${button.textContent}のA地点`;
        heading.textContent = `${button.textContent}のA地点を選択してください`;
        status.textContent = ports.size ? `${ports.size}港から選択できます。` : '登録されている港はありません。';
    };
    const loadMap = async () => {
        if (loaded) return;
        status.textContent = '地図データを読み込んでいます…';
        const body = new FormData();
        body.set('search_type', 'ferry_map_data');
        body.set('csrf', map.dataset.csrf || '');
        try {
            const response = await fetch(window.location.href, { method: 'POST', body, credentials: 'same-origin' });
            if (!response.ok) throw new Error('map request failed');
            const payload = await response.json();
            mapRoutes = Array.isArray(payload.routes) ? payload.routes : [];
            loaded = true;
            clearMapSelection();
        } catch (_) {
            status.textContent = '地図データを取得できませんでした。条件検索をご利用ください。';
        }
    };
    map.querySelectorAll('.ferry-region').forEach((button) => button.addEventListener('click', () => selectRegion(button)));
    map.addEventListener('click', (event) => {
        if (!map.querySelector('.ferry-map-canvas').classList.contains('is-detail')) return;
        if (event.target.closest('button, a')) return;
        clearMapSelection();
    });
    map._loadFerryMap = loadMap;
    if (!map.hidden) loadMap();
});

document.querySelectorAll('[data-ferry-mode]').forEach((button) => {
    button.addEventListener('click', () => {
        const mode = button.dataset.ferryMode;
        document.querySelectorAll('[data-ferry-mode]').forEach((item) => {
            const active = item === button;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
        });
        document.querySelectorAll('[data-ferry-mode-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.ferryModePanel !== mode;
            if (!panel.hidden && mode === 'map') panel._loadFerryMap?.();
        });
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

window.addEventListener('pageshow', () => {
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
});
