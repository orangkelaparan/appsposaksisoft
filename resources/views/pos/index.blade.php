<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Point of Sale · AksiSoft POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
<div id="pos-app" class="min-h-screen" data-store="{{ $store?->id }}" data-warehouse="{{ $warehouse?->id }}" data-session="{{ $session?->id }}">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:px-6">
        <div class="flex items-center gap-3"><a href="{{ route('dashboard') }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#172033] font-black text-white">A</a><div><h1 class="font-bold text-slate-900">Point of Sale</h1><p class="text-xs text-slate-500">{{ $store?->name }} · {{ $warehouse?->name }}</p></div></div>
        <div class="flex items-center gap-2"><span class="badge {{ $session ? 'badge-success' : 'badge-warning' }}">{{ $session ? '● Register open' : '● Register closed' }}</span><span class="hidden text-sm text-slate-600 md:inline">{{ session('user_name') }}</span><a class="btn-secondary px-3 py-2" href="{{ route('dashboard') }}">Exit POS</a></div>
    </header>

    <main class="grid gap-0 xl:grid-cols-[1fr_430px]">
        <section class="min-w-0 border-r border-slate-200 bg-slate-50 p-4 lg:p-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row"><div class="relative flex-1"><input id="search" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="search-autocomplete" class="w-full py-3 pl-10 pr-4" placeholder="Scan barcode or search product (F1)" autofocus><span class="pointer-events-none absolute left-3 top-3.5 text-slate-400">⌕</span><div id="search-autocomplete" role="listbox" class="absolute z-30 mt-2 hidden max-h-96 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"></div></div><button id="shortcut-help" type="button" class="btn-secondary">Shortcuts</button></div>
            <div id="category-filters" class="mb-5 flex gap-2 overflow-x-auto pb-1"><button type="button" data-category="" class="category-filter rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold whitespace-nowrap text-white">All products</button>@foreach($categories as $category)<button type="button" data-category="{{ $category->id }}" class="category-filter rounded-full bg-white px-4 py-2 text-sm font-semibold whitespace-nowrap text-slate-600 ring-1 ring-slate-200 hover:bg-indigo-50">{{ $category->name }}</button>@endforeach</div>
            <div id="product-grid" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"></div>
            <div id="empty-products" class="hidden py-16 text-center text-sm text-slate-400">No matching products were found.</div>
        </section>

        <aside class="flex min-h-[calc(100vh-73px)] flex-col bg-white">
            <div class="border-b border-slate-100 p-5"><div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-900">Current sale</h2><p class="text-xs text-slate-500">{{ $session ? 'Register session #'.$session->id : 'Open a register before checkout' }}</p></div><button id="clear-cart" type="button" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Clear</button></div><div class="mt-4"><label class="field-label">Customer</label><select id="customer" class="w-full px-3 py-2"><option value="">Walk-in customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>@endforeach</select></div></div>
            <div id="cart-lines" class="flex-1 divide-y divide-slate-100 overflow-y-auto"><div id="empty-cart" class="p-10 text-center text-sm text-slate-400">Your cart is empty. Search or tap a product to start a sale.</div></div>
            <div class="border-t border-slate-200 p-5"><div class="mb-3 flex items-center justify-between text-sm"><span class="text-slate-500">Subtotal</span><strong id="subtotal">Rp0</strong></div><div class="mb-3 flex items-center gap-2"><label class="w-20 text-sm text-slate-500">Discount</label><input id="discount" type="number" min="0" step="1" value="0" class="w-full px-3 py-2 text-right text-sm"><span class="text-xs text-slate-400">IDR</span></div><div class="mb-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-4"><span class="font-bold text-slate-800">TOTAL</span><strong id="grand-total" class="text-2xl font-black text-indigo-700">Rp0</strong></div><div class="grid grid-cols-3 gap-2"><button type="button" data-method="cash" class="payment-method rounded-lg border-2 border-indigo-600 bg-indigo-50 px-2 py-2 text-sm font-bold text-indigo-700">Cash<br><span class="text-[10px] font-medium">F6</span></button><button type="button" data-method="qris" class="payment-method rounded-lg border border-slate-200 px-2 py-2 text-sm font-bold text-slate-600">QRIS<br><span class="text-[10px] font-medium">F7</span></button><button type="button" data-method="card" class="payment-method rounded-lg border border-slate-200 px-2 py-2 text-sm font-bold text-slate-600">Card<br><span class="text-[10px] font-medium">F8</span></button></div><label class="field-label mt-4">Cash received</label><input id="tendered" type="number" min="0" step="1" class="w-full px-3 py-2.5 text-right text-lg font-bold" value="0"><div class="mt-2 flex justify-between text-sm"><span class="text-slate-500">Change</span><strong id="change" class="text-emerald-600">Rp0</strong></div><button id="checkout" type="button" {{ $session ? '' : 'disabled' }} class="btn-primary mt-5 w-full py-3.5">Complete sale <span>F9</span></button><p id="pos-message" class="mt-3 text-center text-xs text-slate-500" role="status"></p></div>
        </aside>
    </main>
</div>

<script>
const app = document.getElementById('pos-app');
const initialProducts = @json($products);
const placeholderImage = @json(asset('images/products/placeholder.svg'));
const state = { cart: [], catalog: initialProducts, categoryId: '', method: 'cash', autocomplete: [], activeAutocompleteIndex: -1 };
const searchInput = document.getElementById('search');
const autocompletePanel = document.getElementById('search-autocomplete');
const money = value => 'Rp' + Math.round(Number(value || 0)).toLocaleString('id-ID');
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));

function totals() {
    const subtotal = state.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    const requestedDiscount = Math.max(0, Number(document.getElementById('discount').value || 0));
    const discount = Math.min(requestedDiscount, subtotal);
    const total = Math.max(0, subtotal - discount);
    const tendered = Math.max(0, Number(document.getElementById('tendered').value || 0));

    return { subtotal, discount, total, tendered, change: Math.max(0, tendered - total) };
}

function imageUrl(product) {
    if (!product.image_path) return placeholderImage;
    return product.image_path.startsWith('http') ? product.image_path : '/' + String(product.image_path).replace(/^\/+/, '');
}

function stockBadge(product) {
    const stock = Number(product.stock || 0);
    const threshold = Number(product.low_stock_threshold || 0);
    const tone = stock <= 0 ? 'badge-danger' : (stock <= threshold ? 'badge-warning' : 'badge-success');
    return `<span class="badge ${tone}">${Math.floor(stock).toLocaleString('id-ID')}</span>`;
}

function renderProducts(products) {
    const grid = document.getElementById('product-grid');
    const empty = document.getElementById('empty-products');
    grid.innerHTML = products.map(product => `<button type="button" class="product-card card group p-3 text-left hover:border-indigo-300 hover:shadow-md" data-product-id="${Number(product.id)}"><div class="mb-3 h-24 overflow-hidden rounded-lg bg-gradient-to-br from-indigo-50 to-slate-100"><img src="${escapeHtml(imageUrl(product))}" alt="" class="h-full w-full object-cover" loading="lazy" onerror="this.src='${placeholderImage}'"></div><p class="truncate text-sm font-bold text-slate-800">${escapeHtml(product.name)}</p><p class="mt-1 text-xs text-slate-500">${escapeHtml(product.sku)}</p><div class="mt-3 flex items-center justify-between gap-2"><p class="text-sm font-bold text-indigo-700">${money(product.selling_price)}</p>${stockBadge(product)}</div></button>`).join('');
    empty.classList.toggle('hidden', products.length > 0);
}

function hideAutocomplete() {
    autocompletePanel.classList.add('hidden');
    searchInput.setAttribute('aria-expanded', 'false');
    searchInput.removeAttribute('aria-activedescendant');
    state.activeAutocompleteIndex = -1;
}

function renderAutocomplete(products, query) {
    state.autocomplete = query ? products.slice(0, 8) : [];
    state.activeAutocompleteIndex = -1;
    if (!query) {
        hideAutocomplete();
        return;
    }

    if (!state.autocomplete.length) {
        autocompletePanel.innerHTML = '<p class="px-4 py-3 text-sm text-slate-500">No products match your search.</p>';
    } else {
        autocompletePanel.innerHTML = state.autocomplete.map((product, index) => `<button type="button" id="autocomplete-option-${index}" role="option" aria-selected="false" class="autocomplete-option flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left hover:bg-indigo-50" data-index="${index}"><img src="${escapeHtml(imageUrl(product))}" alt="" class="h-10 w-10 rounded-md border border-slate-100 object-cover" onerror="this.src='${placeholderImage}'"><span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-slate-800">${escapeHtml(product.name)}</span><span class="block truncate text-xs text-slate-500">${escapeHtml(product.sku)} · ${escapeHtml(product.category_name || 'Uncategorized')}</span></span><span class="text-right"><span class="block text-sm font-bold text-indigo-700">${money(product.selling_price)}</span><span class="mt-1 inline-block text-xs text-slate-500">Stock ${Math.floor(Number(product.stock || 0))}</span></span></button>`).join('');
    }
    autocompletePanel.classList.remove('hidden');
    searchInput.setAttribute('aria-expanded', 'true');
}

function updateAutocompleteActive() {
    autocompletePanel.querySelectorAll('.autocomplete-option').forEach((option, index) => {
        const active = index === state.activeAutocompleteIndex;
        option.classList.toggle('bg-indigo-50', active);
        option.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    if (state.activeAutocompleteIndex >= 0) {
        const activeId = `autocomplete-option-${state.activeAutocompleteIndex}`;
        searchInput.setAttribute('aria-activedescendant', activeId);
        document.getElementById(activeId)?.scrollIntoView({ block: 'nearest' });
    } else {
        searchInput.removeAttribute('aria-activedescendant');
    }
}

function selectAutocomplete(index) {
    const product = state.autocomplete[index];
    if (!product) return;
    addProduct(product);
    searchInput.value = '';
    hideAutocomplete();
    loadProducts();
    searchInput.focus();
}

function renderCart() {
    const lines = document.getElementById('cart-lines');
    const empty = document.getElementById('empty-cart');
    const summary = totals();
    empty.classList.toggle('hidden', state.cart.length > 0);
    lines.querySelectorAll('.cart-line').forEach(node => node.remove());

    state.cart.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'cart-line p-4';
        row.innerHTML = `<div class="flex justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-800">${escapeHtml(item.name)}</p><p class="mt-1 text-xs text-slate-500">${escapeHtml(item.sku)} · ${money(item.unit_price)}</p></div><button type="button" class="remove text-rose-500" data-index="${index}" aria-label="Remove item">×</button></div><div class="mt-3 flex items-center justify-between"><div class="flex items-center rounded-lg border border-slate-200"><button type="button" class="quantity px-3 py-1.5" data-index="${index}" data-change="-1">−</button><span class="min-w-10 text-center text-sm font-bold">${item.quantity}</span><button type="button" class="quantity px-3 py-1.5" data-index="${index}" data-change="1">+</button></div><strong class="text-sm text-slate-900">${money(item.quantity * item.unit_price)}</strong></div>`;
        lines.append(row);
    });

    document.getElementById('subtotal').textContent = money(summary.subtotal);
    document.getElementById('grand-total').textContent = money(summary.total);
    document.getElementById('change').textContent = money(summary.change);
}

function addProduct(product) {
    if (Number(product.stock || 0) <= 0) {
        document.getElementById('pos-message').textContent = 'This product is out of stock.';
        return;
    }
    const item = state.cart.find(cartItem => Number(cartItem.id) === Number(product.id));
    if (item) {
        if (item.quantity >= Number(product.stock)) {
            document.getElementById('pos-message').textContent = 'Quantity cannot exceed available stock.';
            return;
        }
        item.quantity += 1;
    } else {
        state.cart.push({ id: Number(product.id), name: product.name, sku: product.sku, stock: Number(product.stock), unit_price: Number(product.selling_price), quantity: 1 });
    }
    document.getElementById('pos-message').textContent = '';
    renderCart();
}

function updateActiveCategory() {
    document.querySelectorAll('.category-filter').forEach(button => {
        const active = String(button.dataset.category || '') === String(state.categoryId || '');
        button.className = active ? 'category-filter rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold whitespace-nowrap text-white' : 'category-filter rounded-full bg-white px-4 py-2 text-sm font-semibold whitespace-nowrap text-slate-600 ring-1 ring-slate-200 hover:bg-indigo-50';
    });
}

let searchTimer;
async function loadProducts() {
    const search = searchInput.value.trim();
    const parameters = new URLSearchParams({ warehouse_id: app.dataset.warehouse, q: search });
    if (state.categoryId) parameters.set('category_id', state.categoryId);
    const grid = document.getElementById('product-grid');
    grid.classList.add('opacity-50');
    try {
        const response = await fetch(`/api/v1/products/search?${parameters.toString()}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Unable to load products.');
        state.catalog = await response.json();
        renderProducts(state.catalog);
        renderAutocomplete(state.catalog, search);
    } catch (error) {
        hideAutocomplete();
        document.getElementById('pos-message').textContent = error.message;
    } finally {
        grid.classList.remove('opacity-50');
    }
}

autocompletePanel.addEventListener('mousedown', event => event.preventDefault());
autocompletePanel.addEventListener('click', event => {
    const option = event.target.closest('.autocomplete-option');
    if (option) selectAutocomplete(Number(option.dataset.index));
});

searchInput.addEventListener('focus', () => {
    if (searchInput.value.trim()) renderAutocomplete(state.catalog, searchInput.value.trim());
});
searchInput.addEventListener('blur', () => window.setTimeout(hideAutocomplete, 150));

searchInput.addEventListener('keydown', event => {
    if (autocompletePanel.classList.contains('hidden')) return;
    const count = state.autocomplete.length;
    if (!count) {
        if (event.key === 'Escape') hideAutocomplete();
        return;
    }
    if (event.key === 'ArrowDown') { event.preventDefault(); state.activeAutocompleteIndex = (state.activeAutocompleteIndex + 1) % count; updateAutocompleteActive(); }
    if (event.key === 'ArrowUp') { event.preventDefault(); state.activeAutocompleteIndex = (state.activeAutocompleteIndex - 1 + count) % count; updateAutocompleteActive(); }
    if (event.key === 'Enter') { event.preventDefault(); selectAutocomplete(state.activeAutocompleteIndex >= 0 ? state.activeAutocompleteIndex : 0); }
    if (event.key === 'Escape') { event.preventDefault(); hideAutocomplete(); }
});

document.getElementById('product-grid').addEventListener('click', event => {
    const card = event.target.closest('.product-card');
    if (!card) return;
    const product = state.catalog.find(item => Number(item.id) === Number(card.dataset.productId));
    if (product) addProduct(product);
});

document.getElementById('category-filters').addEventListener('click', event => {
    const filter = event.target.closest('.category-filter');
    if (!filter) return;
    state.categoryId = filter.dataset.category || '';
    updateActiveCategory();
    loadProducts();
});

document.getElementById('cart-lines').addEventListener('click', event => {
    const remove = event.target.closest('.remove');
    const quantity = event.target.closest('.quantity');
    if (remove) {
        state.cart.splice(Number(remove.dataset.index), 1);
        renderCart();
    }
    if (quantity) {
        const item = state.cart[Number(quantity.dataset.index)];
        item.quantity += Number(quantity.dataset.change);
        if (item.quantity > item.stock) item.quantity = item.stock;
        if (item.quantity < 1) state.cart.splice(Number(quantity.dataset.index), 1);
        renderCart();
    }
});

document.getElementById('clear-cart').addEventListener('click', () => { state.cart = []; document.getElementById('pos-message').textContent = ''; renderCart(); });
document.getElementById('discount').addEventListener('input', renderCart);
document.getElementById('tendered').addEventListener('input', renderCart);
searchInput.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadProducts, 180); });

document.querySelectorAll('.payment-method').forEach(button => button.addEventListener('click', () => {
    state.method = button.dataset.method;
    document.querySelectorAll('.payment-method').forEach(item => item.className = 'payment-method rounded-lg border border-slate-200 px-2 py-2 text-sm font-bold text-slate-600');
    button.className = 'payment-method rounded-lg border-2 border-indigo-600 bg-indigo-50 px-2 py-2 text-sm font-bold text-indigo-700';
}));

document.getElementById('checkout').addEventListener('click', async () => {
    const message = document.getElementById('pos-message');
    const summary = totals();
    if (!state.cart.length) { message.textContent = 'Add at least one item.'; return; }
    if (summary.tendered < summary.total) { message.textContent = 'Payment amount is less than total.'; return; }
    message.textContent = 'Completing sale…';
    const response = await fetch('/api/v1/sales/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ store_id: Number(app.dataset.store), warehouse_id: Number(app.dataset.warehouse), register_session_id: app.dataset.session ? Number(app.dataset.session) : null, customer_id: document.getElementById('customer').value || null, discount_total: summary.discount, payment_method: state.method, tendered_amount: summary.tendered, items: state.cart.map(item => ({ product_id: item.id, quantity: item.quantity, unit_price: item.unit_price })) })
    });
    const payload = await response.json();
    if (!response.ok) { message.textContent = payload.message || Object.values(payload.errors || {}).flat()[0] || 'Checkout failed.'; return; }
    window.open(payload.receipt_url, '_blank');
    state.cart = [];
    document.getElementById('discount').value = 0;
    document.getElementById('tendered').value = 0;
    renderCart();
    message.textContent = `Sale ${payload.invoice_number} completed. Receipt opened.`;
});

document.addEventListener('keydown', event => {
    if (event.key === 'F1') { event.preventDefault(); searchInput.focus(); }
    if (event.key === 'F6') document.querySelector('[data-method="cash"]').click();
    if (event.key === 'F7') document.querySelector('[data-method="qris"]').click();
    if (event.key === 'F8') document.querySelector('[data-method="card"]').click();
    if (event.key === 'F9') { event.preventDefault(); document.getElementById('checkout').click(); }
    if (event.key === 'Escape') { searchInput.value = ''; hideAutocomplete(); loadProducts(); }
});
document.getElementById('shortcut-help').addEventListener('click', () => alert('F1 Search · F6 Cash · F7 QRIS · F8 Card · F9 Complete Sale · ESC Clear search'));

renderProducts(state.catalog);
renderCart();
</script>
</body>
</html>
