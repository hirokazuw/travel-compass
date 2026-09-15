const initialized = new WeakSet();

export function initFlightSuggestions(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
    document.querySelectorAll('[data-flight-city]').forEach((field) => {
        const input = field.querySelector('input[role="combobox"]');
        const code = field.querySelector('input[type="hidden"]');
        const list = field.querySelector('[role="listbox"]');
        const status = field.querySelector('.flight-city-status');
        const cache = new Map();
        let timer, controller, generation = 0, active = -1;
        const close = () => {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            active = -1;
        };
        const render = (items) => {
            list.replaceChildren();
            active = -1;
            items.slice(0, 8).forEach((item, index) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.id = `${list.id}-${index}`;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.textContent = item.label;
                option.addEventListener('click', () => {
                    input.value = item.label;
                    code.value = item.iata;
                    close();
                    status.textContent = '';
                    input.focus();
                });
                list.append(option);
            });
            list.hidden = items.length === 0;
            input.setAttribute('aria-expanded', String(items.length > 0));
            status.textContent = items.length ? '' : '候補がありません。都市名またはIATAコードを直接入力できます。';
        };
        const dismiss = () => {
            generation++;
            clearTimeout(timer);
            controller?.abort();
            close();
            status.textContent = '';
        };
        const reset = () => { code.value = ''; dismiss(); };
        input.addEventListener('flight-city-reset', reset);
        input.addEventListener('input', () => {
            reset();
            const query = input.value.trim();
            if ([...query].length < 2 || [...query].length > 100) return;
            const current = generation;
            timer = setTimeout(async () => {
                const key = query.toLocaleLowerCase();
                if (cache.has(key)) { render(cache.get(key)); return; }
                controller = new AbortController();
                status.textContent = '候補を検索しています…';
                const body = new FormData();
                body.set('search_type', 'flight_city_suggestions');
                body.set('csrf', input.form.elements.csrf.value);
                body.set('query', query);
                try {
                    const response = await fetch(window.location.href, { method: 'POST', body, credentials: 'same-origin', signal: controller.signal });
                    if (!response.ok) throw new Error('suggestions failed');
                    const payload = await response.json();
                    if (current !== generation || input.value.trim() !== query) return;
                    const items = (Array.isArray(payload.suggestions) ? payload.suggestions : [])
                        .filter((item) => typeof item.label === 'string' && /^[A-Z]{3}$/.test(item.iata));
                    if (cache.size >= 20) cache.delete(cache.keys().next().value);
                    cache.set(key, items);
                    render(items);
                } catch (error) {
                    if (error.name === 'AbortError' || current !== generation) return;
                    close();
                    status.textContent = '候補を取得できませんでした。都市名またはIATAコードを直接入力できます。';
                }
            }, 300);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { dismiss(); return; }
            const options = [...list.querySelectorAll('[role="option"]')];
            if (list.hidden || !options.length) return;
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                active = active < 0 ? (event.key === 'ArrowDown' ? 0 : options.length - 1)
                    : (active + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
                options.forEach((option, index) => option.setAttribute('aria-selected', String(index === active)));
                input.setAttribute('aria-activedescendant', options[active].id);
                options[active].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter' && active >= 0) {
                event.preventDefault();
                options[active].click();
            }
        });
        document.addEventListener('click', (event) => { if (!field.contains(event.target)) dismiss(); });
        field.addEventListener('focusout', () => setTimeout(() => { if (!field.contains(document.activeElement)) dismiss(); }, 0));
    });
}
