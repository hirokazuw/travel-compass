const initialized = new WeakSet();

export function initHotelSuggestions(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
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

        const closeSuggestions = () => {
            suggestions.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        };

        const selectSuggestion = (item) => {
            input.value = item.name || input.value;
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
}
