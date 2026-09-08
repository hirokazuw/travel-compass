const initialized = new WeakSet();

export function initFerryMap(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
    const loaders = new WeakMap();

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
        loaders.set(map, loadMap);
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
                if (!panel.hidden && mode === 'map') loaders.get(panel)?.();
            });
        });
    });
}
