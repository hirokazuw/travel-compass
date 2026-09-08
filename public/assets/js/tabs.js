const initialized = new WeakSet();

export function initTabs(document = globalThis.document) {
    if (initialized.has(document)) return;
    initialized.add(document);
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
            const scope = tab.dataset.hotelScope;
            document.querySelectorAll('[data-hotel-result-scope]').forEach((section) => {
                section.hidden = section.dataset.hotelResultScope !== scope;
            });
        });
    });
}
