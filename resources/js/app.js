import './bootstrap';

const initGlobalAnimations = () => {
    const revealTargets = document.querySelectorAll('[data-reveal]');

    if (!revealTargets.length) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        revealTargets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    revealTargets.forEach((el) => el.classList.add('reveal-ready'));

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            const delay = Number(entry.target.getAttribute('data-reveal-delay') || 0);
            window.setTimeout(() => entry.target.classList.add('is-visible'), delay);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });

    revealTargets.forEach((el) => observer.observe(el));
};

initGlobalAnimations();

const initProfileMenu = () => {
    const root = document.getElementById('profileMenuRoot');
    const button = document.getElementById('profileMenuButton');
    const panel = document.getElementById('profileMenuPanel');

    if (!root || !button || !panel) {
        return;
    }

    const close = () => {
        panel.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
        panel.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
    };

    button.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.contains('hidden') ? open() : close();
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            close();
        }
    });
};

initProfileMenu();

const fallbackMoney = (value) => `$${Number(value || 0).toFixed(2)}`;

const initCurrencySwitcher = () => {
    const root = document.getElementById('currencySwitcher');
    if (!root) {
        return {
            format: fallbackMoney,
            getSelectedCode: () => 'USD',
        };
    }

    const button = document.getElementById('currencyButton');
    const panel = document.getElementById('currencyPanel');
    const optionsEl = document.getElementById('currencyOptions');
    const flagEl = document.getElementById('currencyFlag');
    const codeEl = document.getElementById('currencyCode');
    const rateHintEl = document.getElementById('currencyRateHint');
    const statusEl = document.getElementById('currencyStatus');

    if (!button || !panel || !optionsEl || !flagEl || !codeEl || !rateHintEl || !statusEl) {
        return {
            format: fallbackMoney,
            getSelectedCode: () => 'USD',
        };
    }

    const storageKey = 'aethershelf.currency';
    const baseCode = 'USD';
    const currencies = [
        { code: 'USD', name: 'US Dollar', flag: '\u{1F1FA}\u{1F1F8}', locale: 'en-US' },
        { code: 'EUR', name: 'Euro', flag: '\u{1F1EA}\u{1F1FA}', locale: 'de-DE' },
        { code: 'GBP', name: 'Pound Sterling', flag: '\u{1F1EC}\u{1F1E7}', locale: 'en-GB' },
        { code: 'JPY', name: 'Japanese Yen', flag: '\u{1F1EF}\u{1F1F5}', locale: 'ja-JP' },
        { code: 'CAD', name: 'Canadian Dollar', flag: '\u{1F1E8}\u{1F1E6}', locale: 'en-CA' },
        { code: 'AUD', name: 'Australian Dollar', flag: '\u{1F1E6}\u{1F1FA}', locale: 'en-AU' },
        { code: 'SGD', name: 'Singapore Dollar', flag: '\u{1F1F8}\u{1F1EC}', locale: 'en-SG' },
        { code: 'INR', name: 'Indian Rupee', flag: '\u{1F1EE}\u{1F1F3}', locale: 'en-IN' },
        { code: 'THB', name: 'Thai Baht', flag: '\u{1F1F9}\u{1F1ED}', locale: 'th-TH' },
        { code: 'CNY', name: 'Chinese Yuan', flag: '\u{1F1E8}\u{1F1F3}', locale: 'zh-CN' },
    ];
    const currencyMap = new Map(currencies.map((currency) => [currency.code, currency]));

    const getStoredCurrency = () => {
        try {
            return window.localStorage.getItem(storageKey);
        } catch (_) {
            return null;
        }
    };

    const setStoredCurrency = (code) => {
        try {
            window.localStorage.setItem(storageKey, code);
        } catch (_) {
            // Ignore storage write errors.
        }
    };

    const savedCode = getStoredCurrency();
    const state = {
        selectedCode: currencyMap.has(savedCode) ? savedCode : baseCode,
        rates: { [baseCode]: 1 },
        updatedAt: null,
    };

    const getCurrency = (code) => currencyMap.get(code) || currencyMap.get(baseCode);

    const closePanel = () => {
        panel.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    };

    const openPanel = () => {
        panel.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
    };

    const dispatchChange = () => {
        document.dispatchEvent(new CustomEvent('currency:changed', {
            detail: { code: state.selectedCode },
        }));
    };

    const getSelectedRate = () => {
        const rawRate = Number(state.rates[state.selectedCode] || 0);
        return rawRate > 0 ? rawRate : 1;
    };

    const format = (usdValue) => {
        const numeric = Number(usdValue || 0);
        const currency = getCurrency(state.selectedCode);
        const converted = numeric * getSelectedRate();

        try {
            return new Intl.NumberFormat(currency.locale, {
                style: 'currency',
                currency: currency.code,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(converted);
        } catch (_) {
            return fallbackMoney(converted);
        }
    };

    const setStatus = (message, tone = 'normal') => {
        statusEl.textContent = message;
        statusEl.classList.remove('text-slate-500', 'text-rose-600');
        statusEl.classList.add(tone === 'error' ? 'text-rose-600' : 'text-slate-500');
    };

    const renderButton = () => {
        const selected = getCurrency(state.selectedCode);
        const rate = Number(state.rates[selected.code] || 0) || 1;
        flagEl.textContent = selected.flag;
        codeEl.textContent = selected.code;

        if (selected.code === baseCode) {
            rateHintEl.textContent = 'Base USD';
            return;
        }

        rateHintEl.textContent = `1 USD = ${rate.toFixed(4)} ${selected.code}`;
    };

    const renderOptions = () => {
        optionsEl.innerHTML = currencies.map((currency) => {
            const rate = Number(state.rates[currency.code] || 0);
            const isActive = currency.code === state.selectedCode;
            const isUnavailable = currency.code !== baseCode && rate <= 0;
            const rateText = currency.code === baseCode
                ? 'Base currency'
                : isUnavailable
                    ? 'Rate unavailable'
                    : `1 USD = ${rate.toFixed(4)} ${currency.code}`;

            return `
                <button type="button" class="currency-option ${isActive ? 'is-active' : ''}" data-currency-code="${currency.code}" ${isUnavailable ? 'disabled' : ''}>
                    <span class="currency-option-main">
                        <span class="currency-option-flag">${currency.flag}</span>
                        <span>
                            <span class="currency-option-code">${currency.code}</span>
                            <span class="currency-option-name">${currency.name}</span>
                        </span>
                    </span>
                    <span class="currency-option-rate">${rateText}</span>
                </button>
            `;
        }).join('');

        optionsEl.querySelectorAll('[data-currency-code]').forEach((item) => {
            item.addEventListener('click', () => {
                const nextCode = item.dataset.currencyCode || baseCode;
                if (!currencyMap.has(nextCode)) {
                    return;
                }

                state.selectedCode = nextCode;
                setStoredCurrency(nextCode);
                renderButton();
                renderOptions();
                closePanel();
                dispatchChange();
            });
        });
    };

    const formatTimestamp = (value) => {
        if (!value) {
            return '';
        }

        const stamp = new Date(value);
        if (Number.isNaN(stamp.getTime())) {
            return '';
        }

        return stamp.toLocaleString();
    };

    const loadRates = async () => {
        setStatus('Loading live exchange rates...');

        try {
            const response = await fetch('/api/library/currency/rates', {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Unable to fetch rates');
            }

            const payload = await response.json();
            if (!payload?.rates || typeof payload.rates !== 'object') {
                throw new Error('Invalid rate payload');
            }

            state.rates = { [baseCode]: 1, ...payload.rates };
            state.updatedAt = payload.updated_at || null;

            const updatedLabel = formatTimestamp(state.updatedAt);
            if (payload.fallback) {
                setStatus(payload.message || 'Live exchange rates unavailable. Showing USD only.');
            } else if (updatedLabel) {
                setStatus(`Updated ${updatedLabel}`);
            } else {
                setStatus('Live exchange rates active.');
            }

            if (!state.rates[state.selectedCode]) {
                state.selectedCode = baseCode;
                setStoredCurrency(baseCode);
            }
        } catch (_) {
            state.rates = { [baseCode]: 1 };
            state.selectedCode = baseCode;
            setStoredCurrency(baseCode);
            setStatus('Live rates unavailable. Showing USD only.', 'error');
        }

        renderButton();
        renderOptions();
        dispatchChange();
    };

    button.addEventListener('click', (event) => {
        event.stopPropagation();
        panel.classList.contains('hidden') ? openPanel() : closePanel();
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            closePanel();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePanel();
        }
    });

    renderButton();
    renderOptions();
    loadRates();

    return {
        format,
        getSelectedCode: () => state.selectedCode,
    };
};

const currencyExchange = initCurrencySwitcher();
const formatMoney = (value) => currencyExchange.format(value);

const catalogEl = document.getElementById('booksCatalog');

if (catalogEl) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const searchInput = document.getElementById('bookSearch');
    const categorySelect = document.getElementById('bookCategory');
    const availabilitySelect = document.getElementById('bookAvailability');
    const sortSelect = document.getElementById('bookSort');
    const grid = document.getElementById('booksGrid');
    const countEl = document.getElementById('booksCount');

    const api = async (url) => {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to load books');
        }

        return response.json();
    };

    const renderBooks = (books) => {
        countEl.textContent = books.length;

        if (!books.length) {
            grid.innerHTML = '<div class="col-span-full rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-500">No books match your filters.</div>';
            return;
        }

        grid.innerHTML = books.map((book, idx) => {
            const available = Number(book.available_copies) > 0;
            return `
                <article class="book-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" style="animation-delay:${Math.min(idx * 45, 400)}ms">
                    <a href="/books/${book.id}" class="book-cover-frame mb-3">
                        ${book.cover_image_url
                            ? `<img src="${book.cover_image_url}" alt="${book.title} cover" class="book-cover-img">`
                            : `<div class="h-full w-full flex items-center justify-center text-xs font-medium text-slate-500">No cover image</div>`}
                    </a>
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <a href="/books/${book.id}" class="font-semibold text-slate-900 leading-tight hover:text-blue-700">${book.title}</a>
                        <span class="text-xs px-2 py-1 rounded-full ${available ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">${available ? 'Available' : 'Unavailable'}</span>
                    </div>
                    <p class="text-sm text-slate-600">Author: ${book.author}</p>
                    <p class="text-sm text-slate-600">Category: ${book.category?.name || '-'}</p>
                    <p class="text-sm text-slate-600">ISBN: ${book.isbn}</p>
                    <p class="text-sm text-slate-600">Views: ${Number(book.view_count || 0).toLocaleString()}</p>
                    <p class="mt-2 text-sm text-slate-600">${book.description ? book.description : 'No description available yet.'}</p>
                    <p class="mt-2 text-sm font-medium text-slate-700">${book.available_copies}/${book.total_copies} copies in shelf</p>
                </article>
            `;
        }).join('');
    };

    const buildQuery = () => {
        const params = new URLSearchParams();
        const q = searchInput.value.trim();
        if (q) params.set('q', q);
        params.set('category', categorySelect.value);
        params.set('availability', availabilitySelect.value);
        params.set('sort', sortSelect.value);
        return `/api/library/books?${params.toString()}`;
    };

    const loadFilters = async () => {
        const data = await api('/api/library/books/filters');
        data.categories.forEach((name) => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            categorySelect.appendChild(opt);
        });
    };

    const loadBooks = async () => {
        try {
            const books = await api(buildQuery());
            renderBooks(books);
        } catch (e) {
            grid.innerHTML = '<div class="col-span-full rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center text-rose-700">Unable to load books now.</div>';
        }
    };

    let debounceTimer;
    const triggerSearch = () => {
        clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(loadBooks, 280);
    };

    searchInput.addEventListener('input', triggerSearch);
    categorySelect.addEventListener('change', loadBooks);
    availabilitySelect.addEventListener('change', loadBooks);
    sortSelect.addEventListener('change', loadBooks);

    loadFilters().then(loadBooks);
}

const app = document.getElementById('libraryApp');

if (app) {
    const isAdmin = app.dataset.isAdmin === '1';
    const isAuthenticated = app.dataset.isAuthenticated === '1';
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const editBookForm = document.getElementById('editBookForm');
    const editBookId = document.getElementById('editBookId');
    const editTitle = document.getElementById('editTitle');
    const editAuthor = document.getElementById('editAuthor');
    const editCategory = document.getElementById('editCategory');
    const editTotalCopies = document.getElementById('editTotalCopies');
    const editShelfLocation = document.getElementById('editShelfLocation');
    const editDescription = document.getElementById('editDescription');
    const editCoverPreview = document.getElementById('editCoverPreview');
    const authPromptModal = document.getElementById('authPromptModal');
    const authLoginPanel = document.getElementById('authLoginPanel');
    const authRegisterPanel = document.getElementById('authRegisterPanel');
    const checkoutModal = document.getElementById('subscriptionCheckoutModal');
    const checkoutForm = document.getElementById('subscriptionCheckoutForm');
    const checkoutPlanId = document.getElementById('checkoutPlanId');
    const checkoutPlanName = document.getElementById('checkoutPlanName');
    const checkoutPlanMeta = document.getElementById('checkoutPlanMeta');
    const checkoutMethod = document.getElementById('checkoutMethod');
    const checkoutOnlinePanel = document.getElementById('checkoutOnlinePanel');
    const checkoutCardPanel = document.getElementById('checkoutCardPanel');
    const checkoutWalletProvider = document.getElementById('checkoutWalletProvider');
    const checkoutWalletAccount = document.getElementById('checkoutWalletAccount');
    const checkoutCardHolder = document.getElementById('checkoutCardHolder');
    const checkoutCardNumber = document.getElementById('checkoutCardNumber');
    const checkoutCardExpiry = document.getElementById('checkoutCardExpiry');
    const checkoutCardCvv = document.getElementById('checkoutCardCvv');
    const checkoutMessage = document.getElementById('checkoutMessage');
    const checkoutSubmitBtn = document.getElementById('checkoutSubmitBtn');

    const api = async (url, options = {}) => {
        const defaultHeaders = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
        };

        if (!options.body || !(options.body instanceof FormData)) {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            headers: {
                ...defaultHeaders,
                ...(options.headers || {}),
            },
            credentials: 'same-origin',
            ...options,
        });

        if (!response.ok) {
            if (response.status === 401) {
                openAuthPrompt();
                throw new Error('Please login or create an account');
            }

            const err = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(err.message || 'Request failed');
        }

        return response.json();
    };

    const money = formatMoney;
    let selectedCheckoutPlan = null;
    const openAuthPrompt = (tab = 'login') => {
        if (!authPromptModal) {
            window.location.href = '/login';
            return;
        }

        authPromptModal.classList.remove('hidden');
        setAuthTab(tab);
    };
    const closeAuthPrompt = () => {
        if (!authPromptModal) return;
        authPromptModal.classList.add('hidden');
    };
    const setAuthTab = (tab) => {
        if (!authLoginPanel || !authRegisterPanel || !authPromptModal) return;
        const showRegister = tab === 'register';
        authLoginPanel.classList.toggle('hidden', showRegister);
        authRegisterPanel.classList.toggle('hidden', !showRegister);

        authPromptModal.querySelectorAll('.auth-tab-btn').forEach((btn) => {
            const active = btn.dataset.authTab === tab;
            btn.classList.toggle('bg-slate-900', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('bg-slate-200', !active);
            btn.classList.toggle('text-slate-700', !active);
        });
    };
    const requireAuth = (tab = 'login') => {
        if (isAuthenticated) return true;
        openAuthPrompt(tab);
        return false;
    };

    const showCheckoutMessage = (message, type = 'info') => {
        if (!checkoutMessage) return;
        checkoutMessage.textContent = message;
        checkoutMessage.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-rose-50', 'text-rose-700', 'bg-slate-100', 'text-slate-700');
        if (type === 'success') {
            checkoutMessage.classList.add('bg-emerald-50', 'text-emerald-700');
        } else if (type === 'error') {
            checkoutMessage.classList.add('bg-rose-50', 'text-rose-700');
        } else {
            checkoutMessage.classList.add('bg-slate-100', 'text-slate-700');
        }
    };

    const setCheckoutTab = (tab) => {
        if (!checkoutModal || !checkoutMethod || !checkoutOnlinePanel || !checkoutCardPanel) return;

        const isCard = tab === 'card';
        checkoutMethod.value = isCard ? 'card' : 'online';
        checkoutOnlinePanel.classList.toggle('hidden', isCard);
        checkoutCardPanel.classList.toggle('hidden', !isCard);

        checkoutModal.querySelectorAll('.checkout-tab-btn').forEach((btn) => {
            const active = btn.dataset.checkoutTab === tab;
            btn.classList.toggle('bg-slate-900', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('bg-slate-200', !active);
            btn.classList.toggle('text-slate-700', !active);
        });
    };

    const closeCheckout = () => {
        if (!checkoutModal || !checkoutForm) return;
        checkoutModal.classList.add('hidden');
        checkoutForm.reset();
        selectedCheckoutPlan = null;
        if (checkoutMessage) {
            checkoutMessage.classList.add('hidden');
            checkoutMessage.textContent = '';
        }
        setCheckoutTab('online');
    };

    const renderCheckoutPlanMeta = () => {
        if (!checkoutPlanMeta || !selectedCheckoutPlan) return;
        checkoutPlanMeta.textContent = `${money(selectedCheckoutPlan.price)} / ${selectedCheckoutPlan.duration_days} days / limit ${selectedCheckoutPlan.max_borrow_limit}`;
    };

    const openCheckout = (plan) => {
        if (!checkoutModal || !checkoutPlanId || !checkoutPlanName || !checkoutPlanMeta) return;

        selectedCheckoutPlan = plan;
        checkoutPlanId.value = String(plan.id);
        checkoutPlanName.textContent = plan.name;
        renderCheckoutPlanMeta();
        checkoutModal.classList.remove('hidden');
        if (checkoutMessage) {
            checkoutMessage.classList.add('hidden');
            checkoutMessage.textContent = '';
        }
        setCheckoutTab('online');
    };

    if (authPromptModal) {
        authPromptModal.querySelectorAll('[data-auth-close]').forEach((btn) => {
            btn.addEventListener('click', closeAuthPrompt);
        });
        authPromptModal.querySelectorAll('.auth-tab-btn').forEach((btn) => {
            btn.addEventListener('click', () => setAuthTab(btn.dataset.authTab));
        });
    }

    if (checkoutModal) {
        checkoutModal.querySelectorAll('[data-checkout-close]').forEach((btn) => {
            btn.addEventListener('click', closeCheckout);
        });
        checkoutModal.querySelectorAll('.checkout-tab-btn').forEach((btn) => {
            btn.addEventListener('click', () => setCheckoutTab(btn.dataset.checkoutTab));
        });
    }

    const closeBookEditor = () => {
        if (!editBookForm) return;
        editBookForm.classList.add('hidden');
        editBookForm.reset();
        if (editCoverPreview) {
            editCoverPreview.classList.add('hidden');
            editCoverPreview.src = '';
        }
    };
    const openBookEditor = (book) => {
        if (!editBookForm || !editBookId || !editTitle || !editAuthor || !editCategory || !editTotalCopies || !editShelfLocation || !editDescription) {
            return;
        }

        editBookId.value = book.id;
        editTitle.value = book.title || '';
        editAuthor.value = book.author || '';
        editCategory.value = book.category || '';
        editTotalCopies.value = Number(book.total_copies || 1);
        editShelfLocation.value = book.shelf_location || '';
        editDescription.value = book.description || '';

        if (editCoverPreview) {
            if (book.cover_image_url) {
                editCoverPreview.src = book.cover_image_url;
                editCoverPreview.classList.remove('hidden');
            } else {
                editCoverPreview.classList.add('hidden');
                editCoverPreview.src = '';
            }
        }

        editBookForm.classList.remove('hidden');
    };

    const loadSummary = async () => {
        if (!isAuthenticated) {
            document.getElementById('summaryCards').innerHTML = `
                <div class="md:col-span-6 bg-white rounded-lg p-4 shadow">
                    <p class="text-sm text-slate-600">Guest mode: browse books, plans, and e-books for free. Login or register when you want to borrow, reserve, rent, or subscribe.</p>
                </div>
            `;
            return;
        }

        const data = await api('/api/library/summary');
        const cards = [
            ['Books', data.books],
            ['Available', data.available_books],
            ['Borrowed', data.borrowed_active],
            ['Overdue', data.overdue],
            ['Revenue', money(data.total_revenue)],
            ['Subscription', data.my_active_subscription?.plan?.name || 'None'],
        ];

        document.getElementById('summaryCards').innerHTML = cards.map(([k, v]) => `
            <div class="bg-white rounded-lg p-4 shadow">
                <p class="text-xs text-slate-500">${k}</p>
                <p class="font-semibold text-lg">${v}</p>
            </div>`).join('');
    };

    const loadPlans = async () => {
        const plans = await api('/api/library/plans', { headers: { 'Content-Type': 'application/json' } });
        const el = document.getElementById('planList');
        const defaultPlanDescriptions = {
            basic: 'Entry level monthly plan with a smaller borrowing limit.',
            standard: 'Most popular monthly plan with balanced price and access.',
            premium: 'Quarterly high-access plan with the highest borrowing limit.',
        };
        el.innerHTML = plans.map(p => `
            <div class="border rounded p-3 space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        data-plan-toggle="${p.id}"
                        class="planToggle text-left"
                    >
                        <p class="font-medium">${p.name}</p>
                        <p class="text-sm text-slate-600">${money(p.price)} / ${p.duration_days} days / limit ${p.max_borrow_limit}</p>
                    </button>
                    <button
                        data-plan="${p.id}"
                        data-plan-name="${String(p.name || '').replace(/"/g, '&quot;')}"
                        data-plan-price="${Number(p.price || 0)}"
                        data-plan-duration="${Number(p.duration_days || 0)}"
                        data-plan-borrow-limit="${Number(p.max_borrow_limit || 0)}"
                        class="subscribeBtn rounded bg-emerald-600 text-white px-3 py-1 text-sm"
                    >Subscribe</button>
                </div>
                <div data-plan-description="${p.id}" class="hidden text-sm text-slate-600 border-t pt-2">
                    ${p.description || defaultPlanDescriptions[String(p.name || '').toLowerCase()] || 'No description available for this plan.'}
                </div>
            </div>`).join('');

        el.querySelectorAll('.planToggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const descriptionEl = el.querySelector(`[data-plan-description="${btn.dataset.planToggle}"]`);
                if (!descriptionEl) return;
                descriptionEl.classList.toggle('hidden');
            });
        });

        el.querySelectorAll('.subscribeBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!requireAuth('register')) return;
                const plan = {
                    id: Number(btn.dataset.plan),
                    name: btn.dataset.planName || 'Plan',
                    price: Number(btn.dataset.planPrice || 0),
                    duration_days: Number(btn.dataset.planDuration || 0),
                    max_borrow_limit: Number(btn.dataset.planBorrowLimit || 0),
                };
                openCheckout(plan);
            });
        });
    };

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!requireAuth('register')) return;

            const planId = Number(checkoutPlanId?.value || 0);
            if (!planId) {
                showCheckoutMessage('Please select a membership plan.', 'error');
                return;
            }

            const method = checkoutMethod?.value === 'card' ? 'card' : 'online';
            if (method === 'online') {
                if (!checkoutWalletProvider?.value || !checkoutWalletAccount?.value.trim()) {
                    showCheckoutMessage('Please choose wallet provider and wallet account.', 'error');
                    return;
                }
            } else {
                const cardNumber = (checkoutCardNumber?.value || '').replace(/\s+/g, '');
                const expiry = (checkoutCardExpiry?.value || '').trim();
                const cvv = (checkoutCardCvv?.value || '').trim();
                const holder = (checkoutCardHolder?.value || '').trim();

                if (!holder) {
                    showCheckoutMessage('Card holder name is required.', 'error');
                    return;
                }
                if (!/^\d{12,19}$/.test(cardNumber)) {
                    showCheckoutMessage('Card number must be 12 to 19 digits.', 'error');
                    return;
                }
                if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) {
                    showCheckoutMessage('Expiry must be in MM/YY format.', 'error');
                    return;
                }
                if (!/^\d{3,4}$/.test(cvv)) {
                    showCheckoutMessage('CVV must be 3 or 4 digits.', 'error');
                    return;
                }
            }

            if (checkoutSubmitBtn) {
                checkoutSubmitBtn.disabled = true;
                checkoutSubmitBtn.classList.add('opacity-70', 'cursor-not-allowed');
            }
            showCheckoutMessage('Processing your online payment...', 'info');

            try {
                const result = await api('/api/library/subscribe', {
                    method: 'POST',
                    body: JSON.stringify({ plan_id: planId, method }),
                });
                if (result?.status === 'pending_review') {
                    showCheckoutMessage(result.message || 'Payment submitted. Please wait while admin reviews your request.', 'success');
                } else {
                    showCheckoutMessage('Payment submitted.', 'success');
                }
                await refreshAll();
                window.setTimeout(() => closeCheckout(), 1600);
            } catch (err) {
                showCheckoutMessage(err.message || 'Unable to process payment.', 'error');
            } finally {
                if (checkoutSubmitBtn) {
                    checkoutSubmitBtn.disabled = false;
                    checkoutSubmitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            }
        });
    }

    const loadBooks = async () => {
        const books = await api('/api/library/books', { headers: { 'Content-Type': 'application/json' } });
        const el = document.getElementById('bookList');
        el.innerHTML = books.map(book => `
            <div class="border rounded p-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <a href="/books/${book.id}" class="book-cover-thumb shrink-0">
                            ${book.cover_image_url
                                ? `<img src="${book.cover_image_url}" alt="${book.title} cover" class="book-cover-img">`
                                : `<div class="h-full w-full"></div>`}
                        </a>
                        <div>
                        <a href="/books/${book.id}" class="font-medium hover:text-blue-700">${book.title}</a>
                        <p class="text-sm text-slate-600">${book.author} | ${book.category?.name || '-'} | Avail ${book.available_copies}/${book.total_copies} | Views ${Number(book.view_count || 0).toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button data-borrow="${book.id}" class="borrowBtn rounded bg-blue-600 text-white px-2 py-1 text-xs">${isAuthenticated ? 'Borrow' : 'Borrow (Login)'}</button>
                        <button data-reserve="${book.id}" class="reserveBtn rounded bg-amber-600 text-white px-2 py-1 text-xs">${isAuthenticated ? 'Reserve' : 'Reserve (Login)'}</button>
                        ${isAdmin ? `<button data-book="${encodeURIComponent(JSON.stringify({
                            id: book.id,
                            title: book.title,
                            author: book.author,
                            category: book.category?.name || '',
                            total_copies: book.total_copies,
                            shelf_location: book.shelf_location || '',
                            description: book.description || '',
                            cover_image_url: book.cover_image_url || '',
                        }))}" class="editBookBtn rounded bg-emerald-600 text-white px-2 py-1 text-xs">Edit</button>` : ''}
                        ${isAdmin ? `<button data-delete="${book.id}" class="deleteBookBtn rounded bg-red-600 text-white px-2 py-1 text-xs">Delete</button>` : ''}
                    </div>
                </div>
            </div>`).join('');

        el.querySelectorAll('.borrowBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!requireAuth('login')) return;
                try {
                    await api('/api/library/borrow', {
                        method: 'POST',
                        body: JSON.stringify({ book_id: Number(btn.dataset.borrow) }),
                    });
                    await refreshAll();
                } catch (e) {
                    alert(e.message);
                }
            });
        });

        el.querySelectorAll('.reserveBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!requireAuth('login')) return;
                try {
                    await api('/api/library/reserve', {
                        method: 'POST',
                        body: JSON.stringify({ book_id: Number(btn.dataset.reserve), fee: 2.0, method: 'online' }),
                    });
                    await refreshAll();
                } catch (e) {
                    alert(e.message);
                }
            });
        });

        el.querySelectorAll('.deleteBookBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Delete this book?')) return;
                try {
                    await api(`/api/library/books/${btn.dataset.delete}`, { method: 'DELETE' });
                    closeBookEditor();
                    await refreshAll();
                } catch (e) {
                    alert(e.message);
                }
            });
        });

        el.querySelectorAll('.editBookBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                try {
                    const book = JSON.parse(decodeURIComponent(btn.dataset.book || ''));
                    openBookEditor(book);
                } catch (_) {
                    alert('Unable to open edit form for this book');
                }
            });
        });
    };

    const loadMyBorrows = async () => {
        const el = document.getElementById('borrowList');
        if (!isAuthenticated) {
            el.innerHTML = '<div class="border rounded p-2 text-sm text-slate-600">Login to see your borrowed books.</div>';
            return;
        }

        const borrows = await api('/api/library/borrow/my', { headers: { 'Content-Type': 'application/json' } });
        el.innerHTML = borrows.map((b) => {
            const status = String(b.status || '');
            const isActiveBorrow = ['borrowed', 'overdue'].includes(status);

            return `
                <div class="border rounded p-2 text-sm flex items-center justify-between gap-2">
                    <div>
                        <p class="font-medium">${b.book?.title || 'N/A'} (${status})</p>
                        <p class="text-slate-600">Due: ${b.due_date} | Fine: ${money(b.fine_amount)}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        ${isActiveBorrow ? `<a href="/borrowed/${b.id}/reader" class="rounded bg-indigo-600 text-white px-2 py-1 text-xs">Read</a>` : ''}
                        ${status !== 'returned' ? `<button data-return="${b.id}" class="returnBtn rounded bg-slate-800 text-white px-2 py-1 text-xs">Return</button>` : ''}
                    </div>
                </div>`;
        }).join('');

        el.querySelectorAll('.returnBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    await api(`/api/library/borrow/${btn.dataset.return}/return`, { method: 'POST' });
                    await refreshAll();
                } catch (e) {
                    alert(e.message);
                }
            });
        });
    };

    const loadEbooks = async () => {
        const ebooks = await api('/api/library/ebooks', { headers: { 'Content-Type': 'application/json' } });
        const el = document.getElementById('ebookList');

        el.innerHTML = ebooks.map(e => `
            <div class="border rounded p-2 text-sm flex items-center justify-between gap-2">
                <div>
                    <p class="font-medium">${e.title}</p>
                    <p class="text-slate-600">${e.author} | ${money(e.rental_price)} for ${e.rental_days} days</p>
                </div>
                <button data-rent="${e.id}" class="rentBtn rounded bg-indigo-600 text-white px-2 py-1 text-xs">${isAuthenticated ? 'Rent' : 'Rent (Login)'}</button>
            </div>`).join('');

        el.querySelectorAll('.rentBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!requireAuth('login')) return;
                try {
                    await api(`/api/library/ebooks/${btn.dataset.rent}/rent`, { method: 'POST' });
                    await refreshAll();
                } catch (e) {
                    alert(e.message);
                }
            });
        });
    };

    const loadPayments = async () => {
        const el = document.getElementById('paymentList');
        if (!isAuthenticated) {
            el.innerHTML = '<div class="border rounded p-2 text-sm text-slate-600">Login to view your payments.</div>';
            return;
        }

        const payments = await api('/api/library/payments', { headers: { 'Content-Type': 'application/json' } });
        el.innerHTML = payments.slice(0, 20).map(p => `
            <div class="border rounded p-2 text-sm">
                <p class="font-medium">${p.payment_type.toUpperCase()} - ${money(p.amount)}</p>
                <p class="text-slate-600">${p.method} | ${p.status} | ${p.paid_at || '-'}</p>
            </div>`).join('');
    };

    const loadNotifications = async () => {
        const el = document.getElementById('notificationList');
        if (!isAuthenticated) {
            el.innerHTML = '<div class="border rounded p-2 text-sm text-slate-600">Login to receive notifications.</div>';
            return;
        }

        const notifications = await api('/api/library/notifications/my', { headers: { 'Content-Type': 'application/json' } });
        el.innerHTML = notifications.map(n => `
            <div class="border rounded p-2 text-sm">
                <p class="font-medium">${n.type}</p>
                <p class="text-slate-600">${n.message}</p>
                <p class="text-xs text-slate-500">${n.sent_at || n.created_at}</p>
            </div>`).join('');
    };

    const loadRevenueReport = async () => {
        if (!isAdmin || !isAuthenticated) return;
        const report = await api('/api/library/reports/revenue', { headers: { 'Content-Type': 'application/json' } });

        document.getElementById('revenueByType').innerHTML = report.by_type.map(row => `
            <div class="rounded border p-3">
                <p class="text-xs text-slate-500">${row.payment_type}</p>
                <p class="text-lg font-semibold">${money(row.total)}</p>
            </div>`).join('');

        document.getElementById('revenueByMonth').innerHTML = report.by_month
            .map(row => `<div>${row.month}: <strong>${money(row.total)}</strong></div>`)
            .join('') || 'No monthly revenue yet.';
    };

    const loadPendingMembershipPayments = async () => {
        const el = document.getElementById('pendingMembershipPaymentsList');
        if (!el || !isAdmin || !isAuthenticated) return;

        const rows = await api('/api/library/memberships/pending-payments', { headers: { 'Content-Type': 'application/json' } });
        if (!rows.length) {
            el.innerHTML = '<div class="border rounded p-2 text-sm text-slate-600">No pending membership purchase requests.</div>';
            return;
        }

        el.innerHTML = rows.map((row) => `
            <div class="border rounded p-3 text-sm space-y-2">
                <div>
                    <p class="font-medium">${row.user?.name || 'Unknown User'} (${row.user?.email || '-'})</p>
                    <p class="text-slate-600">Requested: ${row.requested_plan?.name || '-'} | Amount ${money(row.amount)} | Method ${row.method}</p>
                    <p class="text-slate-500">Current plan: ${row.current_active_plan?.name || 'None'} | Days left: ${row.current_plan_days_left ?? 0}</p>
                </div>
                <div class="flex gap-2">
                    <button data-review-payment="${row.payment_id}" data-review-action="approve" class="rounded bg-emerald-600 text-white px-2 py-1 text-xs">Approve</button>
                    <button data-review-payment="${row.payment_id}" data-review-action="reject" class="rounded bg-rose-600 text-white px-2 py-1 text-xs">Reject</button>
                </div>
            </div>
        `).join('');

        el.querySelectorAll('[data-review-payment]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const action = btn.dataset.reviewAction;
                const paymentId = btn.dataset.reviewPayment;
                let note = '';
                if (action === 'reject') {
                    note = window.prompt('Optional rejection note for user:') || '';
                }

                try {
                    await api(`/api/library/memberships/pending-payments/${paymentId}/review`, {
                        method: 'POST',
                        body: JSON.stringify({ action, note }),
                    });
                    await refreshAll();
                } catch (err) {
                    alert(err.message || 'Unable to review payment.');
                }
            });
        });
    };

    const loadMembershipOverview = async () => {
        const el = document.getElementById('membershipOverviewList');
        if (!el || !isAdmin || !isAuthenticated) return;

        const rows = await api('/api/library/memberships/overview', { headers: { 'Content-Type': 'application/json' } });
        if (!rows.length) {
            el.innerHTML = '<div class="border rounded p-2 text-sm text-slate-600">No active memberships right now.</div>';
            return;
        }

        el.innerHTML = rows.map((row) => `
            <div class="border rounded p-2 text-sm">
                <p class="font-medium">${row.user?.name || '-'} (${row.user?.email || '-'})</p>
                <p class="text-slate-600">${row.plan?.name || '-'} | ${row.days_left} day(s) left</p>
                <p class="text-xs text-slate-500">${row.start_date} to ${row.end_date}</p>
            </div>
        `).join('');
    };

    const refreshAll = async () => {
        await Promise.all([
            loadSummary(),
            loadPlans(),
            loadBooks(),
            loadMyBorrows(),
            loadEbooks(),
            loadPayments(),
            loadNotifications(),
            loadRevenueReport(),
            loadPendingMembershipPayments(),
            loadMembershipOverview(),
        ]);
    };

    const bindForms = () => {
        const toggleBook = document.getElementById('toggleBookForm');
        const bookForm = document.getElementById('bookForm');
        if (toggleBook && bookForm) {
            toggleBook.addEventListener('click', () => bookForm.classList.toggle('hidden'));
            bookForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const payload = new FormData(bookForm);
                    await api('/api/library/books', {
                        method: 'POST',
                        body: payload,
                    });
                    bookForm.reset();
                    closeBookEditor();
                    await refreshAll();
                } catch (err) {
                    alert(err.message);
                }
            });
        }

        const cancelEditBook = document.getElementById('cancelEditBook');
        if (cancelEditBook) {
            cancelEditBook.addEventListener('click', () => closeBookEditor());
        }

        if (editBookForm && editBookId) {
            editBookForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const payload = new FormData(editBookForm);
                    payload.append('_method', 'PUT');
                    await api(`/api/library/books/${editBookId.value}`, {
                        method: 'POST',
                        body: payload,
                    });
                    closeBookEditor();
                    await refreshAll();
                } catch (err) {
                    alert(err.message);
                }
            });
        }

        const ebookForm = document.getElementById('ebookForm');
        if (ebookForm) {
            ebookForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const fd = new FormData(ebookForm);
                    await api('/api/library/ebooks', {
                        method: 'POST',
                        body: fd,
                        headers: {},
                    });
                    ebookForm.reset();
                    await refreshAll();
                } catch (err) {
                    alert(err.message);
                }
            });
        }

        const sendRemindersBtn = document.getElementById('sendReminders');
        if (sendRemindersBtn) {
            sendRemindersBtn.addEventListener('click', async () => {
                try {
                    const res = await api('/api/library/notifications/due-reminders', { method: 'POST' });
                    await refreshAll();
                    alert(`Generated ${res.count} reminders`);
                } catch (err) {
                    alert(err.message);
                }
            });
        }
    };

    document.addEventListener('currency:changed', () => {
        renderCheckoutPlanMeta();
        refreshAll().catch(() => {
            // Ignore currency refresh errors.
        });
    });

    bindForms();
    refreshAll().catch(() => {
        // Keep the dashboard usable even if one API call fails.
    });
}

const bookDetailActions = document.getElementById('bookDetailActions');

if (bookDetailActions) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const bookId = Number(bookDetailActions.dataset.bookId || 0);
    const isAuthenticated = bookDetailActions.dataset.isAuthenticated === '1';
    const borrowBtn = document.getElementById('borrowBookBtn');
    const reserveBtn = document.getElementById('reserveBookBtn');
    const readBtn = document.getElementById('readBookBtn');
    const planSelect = document.getElementById('bookPlanSelect');
    const subscribeBtn = document.getElementById('subscribeFromBookBtn');
    const messageEl = document.getElementById('bookActionMessage');
    const borrowActionRow = bookDetailActions.querySelector('.borrow-action-row');
    const authPromptModal = document.getElementById('bookAuthPromptModal');
    const authLoginPanel = document.getElementById('bookAuthLoginPanel');
    const authRegisterPanel = document.getElementById('bookAuthRegisterPanel');
    const money = formatMoney;

    const showMessage = (text, type = 'info') => {
        if (!messageEl) return;
        messageEl.textContent = text;
        messageEl.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-rose-50', 'text-rose-700', 'bg-slate-100', 'text-slate-700');
        if (type === 'success') {
            messageEl.classList.add('bg-emerald-50', 'text-emerald-700');
        } else if (type === 'error') {
            messageEl.classList.add('bg-rose-50', 'text-rose-700');
        } else {
            messageEl.classList.add('bg-slate-100', 'text-slate-700');
        }
    };

    const setLoading = (loading) => {
        [borrowBtn, reserveBtn, subscribeBtn].forEach((btn) => {
            if (!btn) return;
            btn.disabled = loading;
            btn.classList.toggle('opacity-70', loading);
            btn.classList.toggle('cursor-not-allowed', loading);
        });
        if (readBtn) {
            readBtn.classList.toggle('opacity-70', loading);
            readBtn.classList.toggle('pointer-events-none', loading);
        }
        if (planSelect) {
            planSelect.disabled = loading;
        }
    };

    const playBorrowReadAnimation = () => {
        if (!readBtn || !borrowActionRow) {
            return;
        }

        readBtn.classList.remove('read-btn-celebrate');
        // Force reflow so animation can replay on repeated borrows.
        void readBtn.offsetWidth;
        readBtn.classList.add('read-btn-celebrate');

        const burst = document.createElement('span');
        burst.className = 'borrow-burst';

        const rowRect = borrowActionRow.getBoundingClientRect();
        const btnRect = readBtn.getBoundingClientRect();
        const centerX = (btnRect.left - rowRect.left) + (btnRect.width / 2);
        const centerY = (btnRect.top - rowRect.top) + (btnRect.height / 2);

        burst.style.setProperty('--burst-x', `${centerX}px`);
        burst.style.setProperty('--burst-y', `${centerY}px`);

        borrowActionRow.appendChild(burst);
        window.setTimeout(() => burst.remove(), 900);
    };

    const setReadButton = (borrowId, animate = false) => {
        if (!readBtn || !borrowId) {
            return;
        }
        readBtn.href = `/borrowed/${borrowId}/reader`;
        readBtn.classList.remove('hidden');
        if (animate) {
            playBorrowReadAnimation();
        }
    };

    const hideReadButton = () => {
        if (!readBtn) {
            return;
        }
        readBtn.classList.add('hidden');
        readBtn.setAttribute('href', '#');
    };

    const setAuthTab = (tab) => {
        if (!authLoginPanel || !authRegisterPanel || !authPromptModal) return;
        const showRegister = tab === 'register';
        authLoginPanel.classList.toggle('hidden', showRegister);
        authRegisterPanel.classList.toggle('hidden', !showRegister);

        authPromptModal.querySelectorAll('.book-auth-tab-btn').forEach((btn) => {
            const active = btn.dataset.bookAuthTab === tab;
            btn.classList.toggle('bg-slate-900', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('bg-slate-200', !active);
            btn.classList.toggle('text-slate-700', !active);
        });
    };

    const openAuthPrompt = (tab = 'login') => {
        if (!authPromptModal) {
            window.location.href = '/login';
            return;
        }

        authPromptModal.classList.remove('hidden');
        setAuthTab(tab);
    };

    const closeAuthPrompt = () => {
        if (!authPromptModal) return;
        authPromptModal.classList.add('hidden');
    };

    const requireAuth = (tab = 'login') => {
        if (isAuthenticated) return true;
        openAuthPrompt(tab);
        return false;
    };

    if (authPromptModal) {
        authPromptModal.querySelectorAll('[data-book-auth-close]').forEach((btn) => {
            btn.addEventListener('click', closeAuthPrompt);
        });
        authPromptModal.querySelectorAll('.book-auth-tab-btn').forEach((btn) => {
            btn.addEventListener('click', () => setAuthTab(btn.dataset.bookAuthTab));
        });
    }

    const api = async (url, options = {}) => {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            ...(options.headers || {}),
        };

        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });

        if (!response.ok) {
            if (response.status === 401) {
                openAuthPrompt('login');
                throw new Error('Please login to continue.');
            }

            const err = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(err.message || 'Request failed');
        }

        return response.json();
    };

    const runAction = async (endpoint, payload) => {
        if (!requireAuth('login')) return;
        if (!bookId) {
            showMessage('Invalid book.', 'error');
            return;
        }

        setLoading(true);
        showMessage('Processing request...', 'info');

        try {
            await api(endpoint, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            showMessage('Success. Your request was completed.', 'success');
            window.setTimeout(() => window.location.reload(), 500);
        } catch (e) {
            if (String(e.message || '').toLowerCase().includes('active membership required')) {
                showMessage('Active membership is required. Please subscribe to a plan from Dashboard.', 'error');
                return;
            }

            showMessage(e.message || 'Unable to complete request.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadCurrentBorrowForBook = async () => {
        if (!isAuthenticated) {
            hideReadButton();
            return;
        }

        try {
            const borrows = await api('/api/library/borrow/my', { method: 'GET' });
            const activeBorrow = (Array.isArray(borrows) ? borrows : []).find((item) => (
                Number(item?.book?.id || 0) === bookId
                && ['borrowed', 'overdue'].includes(String(item?.status || ''))
            ));

            if (activeBorrow?.id) {
                setReadButton(activeBorrow.id, false);
                return;
            }

            hideReadButton();
        } catch (_) {
            hideReadButton();
        }
    };

    if (borrowBtn) {
        borrowBtn.addEventListener('click', async () => {
            if (!requireAuth('login')) return;
            if (!bookId) {
                showMessage('Invalid book.', 'error');
                return;
            }

            setLoading(true);
            showMessage('Processing request...', 'info');

            try {
                const borrow = await api('/api/library/borrow', {
                    method: 'POST',
                    body: JSON.stringify({ book_id: bookId }),
                });
                if (borrow?.id) {
                    setReadButton(borrow.id, true);
                }
                showMessage('Borrowed successfully. You can read this book now.', 'success');
            } catch (e) {
                if (String(e.message || '').toLowerCase().includes('active membership required')) {
                    showMessage('Active membership is required. Please subscribe to a plan from Dashboard.', 'error');
                    return;
                }

                showMessage(e.message || 'Unable to complete request.', 'error');
            } finally {
                setLoading(false);
            }
        });
    }

    if (reserveBtn) {
        reserveBtn.addEventListener('click', () => runAction('/api/library/reserve', { book_id: bookId, fee: 2.0, method: 'online' }));
    }

    const loadPlans = async () => {
        if (!planSelect) return;
        try {
            const plans = await api('/api/library/plans', { method: 'GET' });
            planSelect.innerHTML = '<option value="">Select a plan</option>' + plans.map((p) => (
                `<option value="${p.id}">${p.name} - ${money(p.price)} / ${p.duration_days} days</option>`
            )).join('');
        } catch (_) {
            planSelect.innerHTML = '<option value="">Unable to load plans</option>';
        }
    };

    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', async () => {
            if (!requireAuth('register')) return;
            if (!planSelect || !planSelect.value) {
                showMessage('Please select a membership plan first.', 'error');
                return;
            }

            setLoading(true);
            showMessage('Processing request...', 'info');
            try {
                const result = await api('/api/library/subscribe', {
                    method: 'POST',
                    body: JSON.stringify({ plan_id: Number(planSelect.value), method: 'online' }),
                });
                if (result?.status === 'pending_review') {
                    showMessage('Payment submitted. Please wait 5 minutes while admin reviews your purchase.', 'success');
                } else {
                    showMessage('Subscription request submitted.', 'success');
                }
            } catch (e) {
                showMessage(e.message || 'Unable to subscribe right now.', 'error');
            } finally {
                setLoading(false);
            }
        });
    }

    document.addEventListener('currency:changed', () => {
        loadPlans();
    });

    loadPlans();
    loadCurrentBorrowForBook();
}

const initPurchaseBot = () => {
    const root = document.getElementById('purchaseBot');
    if (!root) return;

    const launcher = document.getElementById('purchaseBotLauncher');
    const panel = document.getElementById('purchaseBotPanel');
    const closeBtn = document.getElementById('purchaseBotClose');
    const messagesEl = document.getElementById('purchaseBotMessages');
    const choicesEl = document.getElementById('purchaseBotChoices');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const isAuthenticated = root.dataset.isAuthenticated === '1';

    if (!launcher || !panel || !messagesEl || !choicesEl) return;

    const state = {
        initialized: false,
        plans: [],
        selectedPlan: null,
        selectedMethod: 'online',
        payer: null,
        seenNotificationIds: new Set(),
        pollIntervalId: null,
    };

    const money = formatMoney;

    const api = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                ...(options.headers || {}),
            },
            ...options,
        });

        if (!response.ok) {
            const err = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(err.message || 'Request failed');
        }

        return response.json();
    };

    const addMessage = (sender, text) => {
        const row = document.createElement('div');
        row.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;

        const bubble = document.createElement('div');
        bubble.className = sender === 'user'
            ? 'max-w-[88%] rounded-xl bg-emerald-600 text-white px-3 py-2 text-sm'
            : 'max-w-[88%] rounded-xl bg-white border border-slate-200 text-slate-700 px-3 py-2 text-sm';
        bubble.textContent = text;

        row.appendChild(bubble);
        messagesEl.appendChild(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const setChoices = (items) => {
        choicesEl.innerHTML = '';
        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = item.className || 'rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100';
            btn.textContent = item.label;
            btn.addEventListener('click', item.onClick);
            choicesEl.appendChild(btn);
        });
    };

    const setForm = (html, onSubmit) => {
        choicesEl.innerHTML = html;
        const form = choicesEl.querySelector('form');
        if (form && onSubmit) {
            form.addEventListener('submit', onSubmit);
        }
    };

    const checkMembershipStatus = async () => {
        addMessage('user', 'Check My Status');
        setChoices([]);
        addMessage('bot', 'Checking your membership status...');

        try {
            const [summary, payments] = await Promise.all([
                api('/api/library/summary', { method: 'GET' }),
                api('/api/library/payments', { method: 'GET' }),
            ]);

            const activeSub = summary?.my_active_subscription;
            const latestMembershipPayment = Array.isArray(payments)
                ? payments.find((p) => p.payment_type === 'membership')
                : null;

            if (activeSub?.plan?.name) {
                const endDate = activeSub.end_date || '-';
                addMessage('bot', `Your membership is active.
Plan: ${activeSub.plan.name}
Ends on: ${endDate}
You can borrow books now.`);
            } else if (latestMembershipPayment?.status === 'pending') {
                addMessage('bot', 'Your payment is pending admin review. Please wait a few minutes.');
            } else if (latestMembershipPayment?.status === 'failed') {
                addMessage('bot', 'Your latest membership payment was rejected. Please try again or contact admin.');
            } else {
                addMessage('bot', 'You do not have an active membership right now.');
            }
        } catch (err) {
            addMessage('bot', `I could not check your status right now: ${err.message}`);
        }

        showMainMenu();
    };

    const showMainMenu = () => {
        addMessage('bot', 'What do you want to do?');
        setChoices([
            {
                label: 'Purchase Plan',
                className: 'rounded-lg bg-emerald-600 text-white px-3 py-1.5 text-sm hover:bg-emerald-700',
                onClick: askPlan,
            },
            {
                label: 'Check My Status',
                onClick: checkMembershipStatus,
            },
        ]);
    };

    const fetchNotifications = async () => {
        if (!isAuthenticated) return [];
        try {
            const items = await api('/api/library/notifications/my', { method: 'GET' });
            return Array.isArray(items) ? items : [];
        } catch (_) {
            return [];
        }
    };

    const syncMembershipNotifications = async () => {
        const notifications = await fetchNotifications();
        notifications.forEach((note) => {
            if (state.seenNotificationIds.has(note.id)) {
                return;
            }

            state.seenNotificationIds.add(note.id);
            const msg = String(note.message || '');
            const isMembership = msg.includes('[Membership]');
            if (!isMembership) return;

            const lower = msg.toLowerCase();
            if (lower.includes('accepted')) {
                addMessage('bot', 'Admin has accepted your payment. Thank you for your purchase.');
            } else if (lower.includes('rejected')) {
                addMessage('bot', `Update from admin: ${msg.replace('[Membership] ', '')}`);
            }
        });
    };

    const startNotificationPolling = () => {
        if (!isAuthenticated || state.pollIntervalId) return;
        state.pollIntervalId = window.setInterval(() => {
            if (!panel.classList.contains('hidden')) {
                syncMembershipNotifications();
            }
        }, 15000);
    };

    const askPlan = async () => {
        addMessage('bot', 'Which plan do you want to join?');

        if (!state.plans.length) {
            try {
                state.plans = await api('/api/library/plans', { method: 'GET' });
            } catch (err) {
                addMessage('bot', `I could not load plans right now: ${err.message}`);
                setChoices([
                    { label: 'Retry', onClick: askPlan },
                ]);
                return;
            }
        }

        setChoices(state.plans.map((plan) => ({
            label: `${plan.name} ${money(plan.price)}`,
            onClick: () => {
                state.selectedPlan = plan;
                state.payer = null;
                addMessage('user', `${plan.name}`);
                addMessage('bot', `Great choice. ${plan.name} costs ${money(plan.price)} for ${plan.duration_days} days.`);
                askPaymentMethod();
            },
        })).concat([
            {
                label: 'Check My Status',
                onClick: checkMembershipStatus,
            },
        ]));
    };

    const askPaymentMethod = () => {
        addMessage('bot', 'How do you want to pay?');
        setChoices([
            {
                label: 'Online Wallet',
                onClick: () => {
                    state.selectedMethod = 'online';
                    addMessage('user', 'Online Wallet');
                    askPayerDetails();
                },
            },
            {
                label: 'Bank Card',
                onClick: () => {
                    state.selectedMethod = 'card';
                    addMessage('user', 'Bank Card');
                    askPayerDetails();
                },
            },
            {
                label: 'Change Plan',
                onClick: askPlan,
            },
            {
                label: 'Check My Status',
                onClick: checkMembershipStatus,
            },
        ]);
    };

    const askPayerDetails = () => {
        if (!state.selectedPlan) {
            askPlan();
            return;
        }

        addMessage('bot', state.selectedMethod === 'card'
            ? 'Enter your card details to continue.'
            : 'Enter your wallet details to continue.'
        );

        if (state.selectedMethod === 'card') {
            setForm(`
                <form class="w-full space-y-2">
                    <input name="holder" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="Card Holder Name" required>
                    <input name="number" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="Card Number" required>
                    <div class="grid grid-cols-2 gap-2">
                        <input name="expiry" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="MM/YY" required>
                        <input name="cvv" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="CVV" required>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded bg-emerald-600 text-white px-3 py-1.5 text-sm">Continue</button>
                        <button type="button" data-back class="rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-700">Back</button>
                    </div>
                </form>
            `, (e) => {
                e.preventDefault();
                const fd = new FormData(e.currentTarget);
                const holder = String(fd.get('holder') || '').trim();
                const number = String(fd.get('number') || '').replace(/\s+/g, '');
                const expiry = String(fd.get('expiry') || '').trim();
                const cvv = String(fd.get('cvv') || '').trim();

                if (!holder) {
                    addMessage('bot', 'Card holder name is required.');
                    return;
                }
                if (!/^\d{12,19}$/.test(number)) {
                    addMessage('bot', 'Card number must be 12 to 19 digits.');
                    return;
                }
                if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) {
                    addMessage('bot', 'Expiry must be MM/YY.');
                    return;
                }
                if (!/^\d{3,4}$/.test(cvv)) {
                    addMessage('bot', 'CVV must be 3 or 4 digits.');
                    return;
                }

                state.payer = { holder, number: `****${number.slice(-4)}`, expiry };
                addMessage('user', `Card ${state.payer.number}`);
                askPaymentReview();
            });

            const backBtn = choicesEl.querySelector('[data-back]');
            if (backBtn) backBtn.addEventListener('click', askPaymentMethod);
            return;
        }

        setForm(`
            <form class="w-full space-y-2">
                <select name="provider" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" required>
                    <option value="">Wallet Provider</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Wave Pay">Wave Pay</option>
                    <option value="KBZ Pay">KBZ Pay</option>
                </select>
                <input name="account" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" placeholder="Wallet Email or Phone" required>
                <div class="flex gap-2">
                    <button class="rounded bg-emerald-600 text-white px-3 py-1.5 text-sm">Continue</button>
                    <button type="button" data-back class="rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-700">Back</button>
                </div>
            </form>
        `, (e) => {
            e.preventDefault();
            const fd = new FormData(e.currentTarget);
            const provider = String(fd.get('provider') || '').trim();
            const account = String(fd.get('account') || '').trim();
            if (!provider || !account) {
                addMessage('bot', 'Wallet provider and account are required.');
                return;
            }
            state.payer = { provider, account };
            addMessage('user', `${provider} (${account})`);
            askPaymentReview();
        });
        const backBtn = choicesEl.querySelector('[data-back]');
        if (backBtn) backBtn.addEventListener('click', askPaymentMethod);
    };

    const askPaymentReview = () => {
        if (!state.selectedPlan || !state.payer) {
            askPlan();
            return;
        }

        const payerText = state.selectedMethod === 'card'
            ? `${state.payer.holder} | ${state.payer.number} | Exp ${state.payer.expiry}`
            : `${state.payer.provider} | ${state.payer.account}`;

        addMessage('bot', `Order review:
Plan: ${state.selectedPlan.name}
Amount: ${money(state.selectedPlan.price)}
Method: ${state.selectedMethod === 'card' ? 'Bank Card' : 'Online Wallet'}
Payer: ${payerText}`);

        setChoices([
            {
                label: `Confirm Pay ${money(state.selectedPlan.price)}`,
                className: 'rounded-lg bg-emerald-600 text-white px-3 py-1.5 text-sm hover:bg-emerald-700',
                onClick: async () => {
                    addMessage('user', 'Confirm Payment');
                    setChoices([]);
                    addMessage('bot', 'Processing your payment...');

                    try {
                        const result = await api('/api/library/subscribe', {
                            method: 'POST',
                            body: JSON.stringify({
                                plan_id: Number(state.selectedPlan.id),
                                method: state.selectedMethod,
                            }),
                        });
                        const requestRef = result?.payment?.id ? `PAY-${result.payment.id}` : 'PENDING';
                        addMessage('bot', `Payment request submitted.
Request: ${requestRef}
Plan: ${state.selectedPlan.name}
Amount: ${money(state.selectedPlan.price)}
Status: Pending admin review
Please wait 5 minutes. Admin will review and accept your payment.`);
                        setChoices([
                            {
                                label: 'Check Review Status',
                                onClick: async () => {
                                    addMessage('user', 'Check review status');
                                    const notifications = await fetchNotifications();
                                    const latestMembershipUpdate = notifications.find((n) => {
                                        const message = String(n.message || '').toLowerCase();
                                        return message.includes('[membership]') && (message.includes('accepted') || message.includes('rejected'));
                                    });
                                    if (!latestMembershipUpdate) {
                                        addMessage('bot', 'Your payment is still pending. Please wait for admin review.');
                                        return;
                                    }
                                    addMessage('bot', latestMembershipUpdate.message.replace('[Membership] ', ''));
                                },
                            },
                            {
                                label: 'Check My Status',
                                onClick: checkMembershipStatus,
                            },
                            {
                                label: 'Purchase Another Plan',
                                onClick: () => {
                                    state.selectedPlan = null;
                                    state.payer = null;
                                    askPlan();
                                },
                            },
                        ]);
                    } catch (err) {
                        addMessage('bot', `Payment failed: ${err.message}`);
                        setChoices([
                            { label: 'Try Again', onClick: askPaymentReview },
                            { label: 'Choose Payment Method', onClick: askPaymentMethod },
                        ]);
                    }
                },
            },
            {
                label: 'Edit Payer Details',
                onClick: askPayerDetails,
            },
            {
                label: 'Change Payment Method',
                onClick: askPaymentMethod,
            },
        ]);
    };

    const startConversation = async () => {
        messagesEl.innerHTML = '';
        choicesEl.innerHTML = '';
        state.selectedPlan = null;
        state.selectedMethod = 'online';
        state.payer = null;

        addMessage('bot', 'Hi! I can help you purchase a subscription plan.');

        if (!isAuthenticated) {
            addMessage('bot', 'Please login first, then I can complete your purchase in chat.');
            setChoices([
                {
                    label: 'Login',
                    className: 'rounded-lg bg-slate-900 text-white px-3 py-1.5 text-sm hover:bg-slate-800',
                    onClick: () => { window.location.href = '/login'; },
                },
                {
                    label: 'Register',
                    className: 'rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100',
                    onClick: () => { window.location.href = '/register'; },
                },
            ]);
            return;
        }

        const notifications = await fetchNotifications();
        notifications.forEach((note) => state.seenNotificationIds.add(note.id));
        startNotificationPolling();
        showMainMenu();
    };

    launcher.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        if (!state.initialized) {
            startConversation();
            state.initialized = true;
        } else if (!panel.classList.contains('hidden')) {
            syncMembershipNotifications();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => panel.classList.add('hidden'));
    }
};

initPurchaseBot();

