const initialized = new WeakSet();

export function initFerrySearch(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
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
}
