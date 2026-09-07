<script setup>
import PosTerminalLayout from '@/Layouts/PosTerminalLayout.vue';
import InputError from '@/Components/InputError.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    register: { type: Object, required: true },
    sessionOpen: { type: Boolean, default: false },
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const searchInput = ref(null);
const customerSearchInput = ref(null);
const query = ref('');
const categoryId = ref('');
const results = ref([...props.products]);
const cart = ref([]);
const selectedIndex = ref(-1);
const receipt = ref(null);
const showShortcuts = ref(false);
const showHolds = ref(false);
const showMore = ref(false);
const showCustomerDrawer = ref(false);
const showCashPanel = ref(false);
const showCalculator = ref(false);
const calcDisplay = ref('0');
const calcAccumulator = ref(null);
const calcOperator = ref(null);
const calcFresh = ref(true);
const holds = ref([]);
const holdNotice = ref('');
const discountDraft = ref('');
const discountMode = ref('percent');
const cartDiscountAmount = ref(0);
const customerList = ref([...props.customers]);
const customerSearch = ref('');
const showNewCustomer = ref(false);
const newCustomerSaving = ref(false);
const newCustomerError = ref('');
const newCustomer = ref({
    name: '',
    phone: '',
    email: '',
});

const form = useForm({
    pos_register_id: props.register.id,
    customer_id: '',
    customer_name: 'Walk-in',
    payment_method: 'cash',
    payment_reference: '',
    amount_tendered: '',
    cart_discount_amount: 0,
    items: [],
});

const holdsKey = computed(() => `nexcore_pos_holds_${props.register.id}`);
const isCash = computed(() => form.payment_method === 'cash');

const paymentReferenceLabel = computed(() => {
    switch (form.payment_method) {
        case 'card':
            return 'Card / approval number';
        case 'nagad':
            return 'Nagad number / TrxID';
        case 'bkash':
            return 'bKash number / TrxID';
        case 'bank':
            return 'Bank reference / account';
        case 'mobile':
            return 'Mobile payment number';
        default:
            return 'Payment number';
    }
});

const money = (value) =>
    `৳ ${Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;

const selectedCustomer = computed(() =>
    customerList.value.find((row) => String(row.id) === String(form.customer_id)) || null,
);

const filteredCustomers = computed(() => {
    const q = customerSearch.value.trim().toLowerCase();
    if (!q) {
        return customerList.value.slice(0, 40);
    }

    return customerList.value
        .filter((row) => {
            const hay = `${row.name || ''} ${row.phone || ''} ${row.code || ''} ${row.email || ''}`.toLowerCase();
            return hay.includes(q);
        })
        .slice(0, 40);
});

const cartItemCount = computed(() =>
    cart.value.reduce((sum, line) => sum + Number(line.quantity || 0), 0),
);

const brokenImages = ref({});

const markImageBroken = (productId) => {
    brokenImages.value = { ...brokenImages.value, [productId]: true };
};

const productImageVisible = (product) =>
    Boolean(product?.image_url) && !brokenImages.value[product.id];

const stockLabel = (product) => {
    const qty = Number(product?.stock_available ?? 0);
    if (!Number.isFinite(qty)) {
        return 'Stock —';
    }
    const display = Number.isInteger(qty) ? String(qty) : qty.toFixed(2).replace(/\.?0+$/, '');
    return `Stock ${display}`;
};

const pickCustomer = (customer) => {
    form.customer_id = customer.id;
    form.customer_name = customer.name;
    showCustomerDrawer.value = false;
    showNewCustomer.value = false;
    customerSearch.value = '';
    holdNotice.value = '';
};

const clearCustomer = () => {
    form.customer_id = '';
    form.customer_name = 'Walk-in';
};

const openCustomerDrawer = async () => {
    showCustomerDrawer.value = true;
    showNewCustomer.value = false;
    customerSearch.value = '';
    newCustomerError.value = '';
    await nextTick();
    customerSearchInput.value?.focus();
};

const createCustomer = async () => {
    newCustomerError.value = '';
    if (!newCustomer.value.name.trim()) {
        newCustomerError.value = 'Name is required.';
        return;
    }

    newCustomerSaving.value = true;
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(route('pos.terminal.customers.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                name: newCustomer.value.name.trim(),
                phone: newCustomer.value.phone.trim() || null,
                email: newCustomer.value.email.trim() || null,
            }),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            newCustomerError.value = data?.message
                || data?.errors?.name?.[0]
                || data?.errors?.phone?.[0]
                || data?.errors?.email?.[0]
                || 'Could not create customer.';
            return;
        }

        const created = data.customer;
        customerList.value = [created, ...customerList.value.filter((row) => row.id !== created.id)];
        newCustomer.value = { name: '', phone: '', email: '' };
        showNewCustomer.value = false;
        pickCustomer(created);
        holdNotice.value = `Customer ${created.name} added.`;
    } catch {
        newCustomerError.value = 'Could not create customer.';
    } finally {
        newCustomerSaving.value = false;
    }
};

const lineGross = (line) => Number(line.unit_price) * Number(line.quantity);
const lineDiscount = (line) => lineGross(line) * (Math.min(100, Math.max(0, Number(line.discount_percent || 0))) / 100);
const lineNet = (line) => lineGross(line) - lineDiscount(line);

const cartSubtotal = computed(() => cart.value.reduce((sum, line) => sum + lineGross(line), 0));
const lineDiscountTotal = computed(() => cart.value.reduce((sum, line) => sum + lineDiscount(line), 0));
const totalDiscount = computed(() => lineDiscountTotal.value + Number(cartDiscountAmount.value || 0));
const taxAmount = computed(() => 0);
const grandTotal = computed(() =>
    Math.max(0, cartSubtotal.value - totalDiscount.value + taxAmount.value),
);
const changeDue = computed(() =>
    isCash.value ? Math.max(0, Number(form.amount_tendered || 0) - grandTotal.value) : 0,
);

const quickTenders = computed(() => {
    const total = grandTotal.value;
    if (total <= 0 || !isCash.value) {
        return [];
    }
    const exact = Number(total.toFixed(2));
    const rounds = [50, 100, 200, 500, 1000]
        .map((step) => Math.ceil(exact / step) * step)
        .filter((value, index, list) => value >= exact && list.indexOf(value) === index)
        .slice(0, 3);

    return [exact, ...rounds.filter((value) => value !== exact)].slice(0, 4);
});

watch(grandTotal, (value) => {
    if (!isCash.value) {
        form.amount_tendered = value ? value.toFixed(2) : '';
        return;
    }
    if (!form.amount_tendered || Number(form.amount_tendered) < value) {
        form.amount_tendered = value ? value.toFixed(2) : '';
    }
});

watch(
    () => form.payment_method,
    (method) => {
        if (method !== 'cash') {
            form.amount_tendered = grandTotal.value ? grandTotal.value.toFixed(2) : '';
            showCashPanel.value = false;
        } else {
            form.payment_reference = '';
        }
    },
);

watch(
    () => flash.value?.receipt,
    (value) => {
        if (value) {
            receipt.value = value;
        }
    },
    { immediate: true },
);

const loadHolds = () => {
    try {
        const raw = localStorage.getItem(holdsKey.value);
        holds.value = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(holds.value)) {
            holds.value = [];
        }
    } catch {
        holds.value = [];
    }
};

const persistHolds = () => {
    localStorage.setItem(holdsKey.value, JSON.stringify(holds.value.slice(0, 12)));
};

let searchTimer = null;

const fetchProducts = async () => {
    const params = new URLSearchParams();
    if (query.value.trim()) {
        params.set('q', query.value.trim());
    }
    if (categoryId.value) {
        params.set('category_id', String(categoryId.value));
    }

    const res = await fetch(`${route('pos.terminal.search')}?${params}`);
    const data = await res.json();
    results.value = Array.isArray(data) ? data : (data.results ?? []);

    return data;
};

watch([query, categoryId], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        if (!query.value.trim() && !categoryId.value) {
            results.value = [...props.products];
            return;
        }
        await fetchProducts();
    }, 220);
});

const onScanEnter = async () => {
    const code = query.value?.trim();
    if (!code) {
        return;
    }

    const data = await fetchProducts();
    const exact = data.exact ?? null;

    if (exact) {
        addProduct(exact);
        query.value = '';
        results.value = categoryId.value
            ? (await fetchProducts()).results ?? []
            : [...props.products];
        await nextTick();
        searchInput.value?.focus();
    }
};

const focusScan = () => {
    searchInput.value?.focus();
    searchInput.value?.select?.();
};

const addProduct = (product) => {
    if (!product.price || !product.in_stock) {
        return;
    }

    const existingIndex = cart.value.findIndex((line) => line.product_id === product.id);
    if (existingIndex >= 0) {
        cart.value[existingIndex].quantity = Number(cart.value[existingIndex].quantity) + 1;
        selectedIndex.value = existingIndex;
        return;
    }

    cart.value.push({
        product_id: product.id,
        product_variant_id: null,
        name: product.name,
        sku: product.sku,
        image_url: product.image_url || null,
        quantity: 1,
        unit_price: product.price,
        discount_percent: 0,
        currency: product.currency,
    });
    selectedIndex.value = cart.value.length - 1;
};

const selectLine = (index) => {
    selectedIndex.value = index;
};

const removeLine = (index) => {
    cart.value.splice(index, 1);
    if (!cart.value.length) {
        selectedIndex.value = -1;
        return;
    }
    selectedIndex.value = Math.min(index, cart.value.length - 1);
};

const clearCart = () => {
    cart.value = [];
    selectedIndex.value = -1;
    form.amount_tendered = '';
    form.payment_reference = '';
    form.customer_id = '';
    form.customer_name = 'Walk-in';
    cartDiscountAmount.value = 0;
    discountDraft.value = '';
    showCashPanel.value = false;
};

const bumpQty = (delta) => {
    if (selectedIndex.value < 0) {
        return;
    }
    const line = cart.value[selectedIndex.value];
    if (!line) {
        return;
    }
    line.quantity = Math.max(1, Number(line.quantity) + delta);
};

const bumpLineQty = (index, delta) => {
    selectLine(index);
    const line = cart.value[index];
    if (!line) {
        return;
    }
    line.quantity = Math.max(1, Number(line.quantity) + delta);
};

const applyCartDiscount = () => {
    const raw = Number(discountDraft.value);
    if (!Number.isFinite(raw) || raw < 0) {
        return;
    }

    const afterLines = Math.max(0, cartSubtotal.value - lineDiscountTotal.value);

    if (discountMode.value === 'percent') {
        const pct = Math.min(100, raw);
        cartDiscountAmount.value = Number(((afterLines * pct) / 100).toFixed(2));
    } else {
        cartDiscountAmount.value = Number(Math.min(afterLines, raw).toFixed(2));
    }
};

const clearCartDiscount = () => {
    cartDiscountAmount.value = 0;
    discountDraft.value = '';
};

const snapshotCart = () => ({
    customer_id: form.customer_id || '',
    customer_name: form.customer_name || 'Walk-in',
    payment_method: form.payment_method || 'cash',
    cart_discount_amount: cartDiscountAmount.value || 0,
    items: cart.value.map((line) => ({
        product_id: line.product_id,
        product_variant_id: line.product_variant_id,
        name: line.name,
        sku: line.sku,
        image_url: line.image_url || null,
        quantity: line.quantity,
        unit_price: line.unit_price,
        discount_percent: line.discount_percent || 0,
        currency: line.currency,
    })),
});

const holdCart = () => {
    if (!cart.value.length) {
        holdNotice.value = 'Cart is empty.';
        return;
    }

    const snap = snapshotCart();
    holds.value.unshift({
        id: `${Date.now()}`,
        label: `${snap.customer_name} · ${snap.items.length} item${snap.items.length === 1 ? '' : 's'}`,
        savedAt: new Date().toISOString(),
        total: grandTotal.value,
        ...snap,
    });
    persistHolds();
    clearCart();
    holdNotice.value = 'Sale held.';
    showHolds.value = true;
};

const recallHold = (hold) => {
    if (cart.value.length) {
        const snap = snapshotCart();
        holds.value.unshift({
            id: `${Date.now()}`,
            label: `${snap.customer_name} · ${snap.items.length} item${snap.items.length === 1 ? '' : 's'}`,
            savedAt: new Date().toISOString(),
            total: grandTotal.value,
            ...snap,
        });
    }

    cart.value = (hold.items || []).map((line) => ({ ...line, discount_percent: line.discount_percent || 0 }));
    form.customer_id = hold.customer_id || '';
    form.customer_name = hold.customer_name || 'Walk-in';
    form.payment_method = hold.payment_method || 'cash';
    cartDiscountAmount.value = Number(hold.cart_discount_amount || 0);
    selectedIndex.value = cart.value.length ? 0 : -1;
    holds.value = holds.value.filter((row) => row.id !== hold.id);
    persistHolds();
    showHolds.value = false;
    holdNotice.value = 'Hold recalled.';
};

const deleteHold = (holdId) => {
    holds.value = holds.value.filter((row) => row.id !== holdId);
    persistHolds();
};

const proceedToPayment = () => {
    if (!cart.value.length || form.processing) {
        return;
    }

    if (isCash.value) {
        form.amount_tendered = grandTotal.value ? grandTotal.value.toFixed(2) : '';
        showCashPanel.value = true;
        return;
    }

    if (!String(form.payment_reference || '').trim()) {
        form.setError('payment_reference', `${paymentReferenceLabel.value} is required.`);
        return;
    }

    form.clearErrors('payment_reference');
    complete();
};

const complete = () => {
    if (!cart.value.length || form.processing) {
        return;
    }

    form.items = cart.value.map((line) => ({
        product_id: line.product_id,
        product_variant_id: line.product_variant_id,
        quantity: line.quantity,
        discount_percent: line.discount_percent || 0,
    }));
    form.cart_discount_amount = cartDiscountAmount.value || 0;

    if (!isCash.value) {
        form.amount_tendered = grandTotal.value.toFixed(2);
        if (!String(form.payment_reference || '').trim()) {
            form.setError('payment_reference', `${paymentReferenceLabel.value} is required.`);
            return;
        }
    } else {
        form.payment_reference = '';
    }

    form.post(route('pos.terminal.complete'), {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            selectedIndex.value = -1;
            cartDiscountAmount.value = 0;
            discountDraft.value = '';
            showCashPanel.value = false;
            form.reset('amount_tendered', 'items', 'cart_discount_amount', 'payment_reference');
            form.customer_id = '';
            form.customer_name = 'Walk-in';
            form.payment_method = 'cash';
            form.pos_register_id = props.register.id;
            nextTick(() => searchInput.value?.focus());
        },
    });
};

const closeReceipt = () => {
    receipt.value = null;
    nextTick(() => searchInput.value?.focus());
};

const printReceipt = () => window.print();

const resetCalculator = () => {
    calcDisplay.value = '0';
    calcAccumulator.value = null;
    calcOperator.value = null;
    calcFresh.value = true;
};

const openCalculator = () => {
    resetCalculator();
    showCalculator.value = true;
    showMore.value = false;
};

const calcApplyOperator = () => {
    const current = Number(calcDisplay.value);
    if (calcAccumulator.value === null || !calcOperator.value) {
        calcAccumulator.value = current;
        return;
    }

    const left = Number(calcAccumulator.value);
    let result = left;

    switch (calcOperator.value) {
        case '+':
            result = left + current;
            break;
        case '-':
            result = left - current;
            break;
        case '*':
            result = left * current;
            break;
        case '/':
            result = current === 0 ? left : left / current;
            break;
        default:
            result = current;
    }

    calcDisplay.value = String(Number(result.toFixed(8)));
    calcAccumulator.value = Number(calcDisplay.value);
};

const calcPressDigit = (digit) => {
    if (calcFresh.value || calcDisplay.value === '0') {
        calcDisplay.value = String(digit);
        calcFresh.value = false;
        return;
    }

    if (calcDisplay.value.replace('.', '').length >= 12) {
        return;
    }

    calcDisplay.value += String(digit);
};

const calcPressDot = () => {
    if (calcFresh.value) {
        calcDisplay.value = '0.';
        calcFresh.value = false;
        return;
    }

    if (!calcDisplay.value.includes('.')) {
        calcDisplay.value += '.';
    }
};

const calcPressOperator = (op) => {
    if (!calcFresh.value) {
        calcApplyOperator();
    }
    calcOperator.value = op;
    calcFresh.value = true;
};

const calcEquals = () => {
    calcApplyOperator();
    calcOperator.value = null;
    calcFresh.value = true;
};

const calcBackspace = () => {
    if (calcFresh.value) {
        return;
    }

    if (calcDisplay.value.length <= 1) {
        calcDisplay.value = '0';
        calcFresh.value = true;
        return;
    }

    calcDisplay.value = calcDisplay.value.slice(0, -1);
};

const useCalcAsTendered = () => {
    const value = Number(calcDisplay.value);
    if (Number.isNaN(value)) {
        return;
    }

    form.amount_tendered = String(value);
    showCashPanel.value = true;
    showCalculator.value = false;
};

const isTypingTarget = (event) => {
    const tag = event.target?.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || event.target?.isContentEditable;
};

const onGlobalKeydown = (event) => {
    if (event.key === 'Escape') {
        if (receipt.value) {
            event.preventDefault();
            closeReceipt();
            return;
        }
        showHolds.value = false;
        showShortcuts.value = false;
        showCalculator.value = false;
        showCustomerDrawer.value = false;
        showMore.value = false;
        showCashPanel.value = false;
        return;
    }

    if (event.key === 'F2') {
        event.preventDefault();
        focusScan();
        return;
    }

    if (event.key === 'F4') {
        event.preventDefault();
        openCalculator();
        return;
    }

    if (event.key === 'F6') {
        event.preventDefault();
        holdCart();
        return;
    }

    if (event.key === 'F7') {
        event.preventDefault();
        showHolds.value = !showHolds.value;
        return;
    }

    if (event.key === 'F9' || (event.key === 'Enter' && event.ctrlKey)) {
        event.preventDefault();
        if (showCashPanel.value) {
            complete();
        } else {
            proceedToPayment();
        }
        return;
    }

    if (isTypingTarget(event)) {
        return;
    }

    if ((event.key === '+' || event.key === '=') && selectedIndex.value >= 0) {
        event.preventDefault();
        bumpQty(1);
        return;
    }

    if (event.key === '-' && selectedIndex.value >= 0) {
        event.preventDefault();
        bumpQty(-1);
        return;
    }

    if ((event.key === 'Delete' || event.key === 'Backspace') && selectedIndex.value >= 0) {
        event.preventDefault();
        removeLine(selectedIndex.value);
    }
};

onMounted(() => {
    loadHolds();
    window.addEventListener('keydown', onGlobalKeydown);
    searchInput.value?.focus();
});

onUnmounted(() => {
    window.removeEventListener('keydown', onGlobalKeydown);
});

const paymentIcon = (value) => {
    if (value === 'cash') return '💵';
    if (value === 'card') return '💳';
    if (value === 'nagad') return 'N';
    if (value === 'bkash') return 'b';
    if (value === 'bank') return '🏦';
    return '•';
};
</script>

<template>
    <Head title="POS Terminal" />

    <PosTerminalLayout title="POS Terminal">
        <template #header-actions>
            <div class="hidden items-center gap-3 text-xs text-slate-500 md:flex">
                <span
                    class="rounded-full px-2.5 py-1 font-medium"
                    :class="sessionOpen ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                >
                    {{ sessionOpen ? 'Session open' : 'Auto-open on sale' }}
                </span>
                <span>{{ register.name }}</span>
                <span>Today {{ stats.completed_today }} · {{ money(stats.revenue_today) }}</span>
            </div>
        </template>

        <div class="grid h-full min-h-0 gap-4 xl:grid-cols-[minmax(0,1.55fr)_minmax(360px,0.9fr)]">
            <!-- Catalog -->
            <section class="flex min-h-0 flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="space-y-3 border-b border-slate-100 p-4">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            placeholder="Search product by name, barcode or SKU..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-12 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#2563eb] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2563eb]/20"
                            autocomplete="off"
                            @keydown.enter.prevent="onScanEnter"
                        />
                        <button
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-[#2563eb]"
                            title="Scan barcode"
                            @click="focusScan"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M4 17v2a1 1 0 001 1h2m10-16h2a1 1 0 011 1v2m0 10v2a1 1 0 01-1 1h-2M8 8h.01M12 8h.01M16 8h.01M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex gap-2 overflow-x-auto pb-0.5">
                        <button
                            type="button"
                            class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition"
                            :class="!categoryId ? 'bg-[#2563eb] text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                            @click="categoryId = ''"
                        >
                            All
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition"
                            :class="String(categoryId) === String(cat.id) ? 'bg-[#2563eb] text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                            @click="categoryId = cat.id"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3">
                    <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-6">
                        <button
                            v-for="product in results"
                            :key="product.id"
                            type="button"
                            class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:border-[#2563eb]/50 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
                            :disabled="!product.price || !product.in_stock"
                            @click="addProduct(product)"
                        >
                            <div class="relative aspect-square w-full overflow-hidden bg-slate-100">
                                <img
                                    v-if="productImageVisible(product)"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                    loading="lazy"
                                    @error="markImageBroken(product.id)"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-slate-100 text-2xl font-bold text-slate-300"
                                >
                                    {{ product.name?.charAt(0) || '?' }}
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col gap-1 px-2.5 py-2">
                                <p class="line-clamp-2 min-h-[2.25rem] text-xs font-medium leading-snug text-slate-700">
                                    {{ product.name }}
                                </p>
                                <p class="mt-auto text-sm font-bold tabular-nums text-slate-900">
                                    <template v-if="product.price">{{ money(product.price) }}</template>
                                    <span v-else class="font-medium text-slate-400">No price</span>
                                </p>
                                <p
                                    class="text-[11px] font-medium tabular-nums"
                                    :class="product.in_stock ? 'text-slate-500' : 'text-red-500'"
                                >
                                    {{ stockLabel(product) }}
                                </p>
                            </div>
                        </button>
                    </div>
                    <p v-if="!results.length" class="py-16 text-center text-sm text-slate-400">No products match this search.</p>
                </div>

                <div class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50/80 p-3 sm:grid-cols-5">
                    <button type="button" class="flex items-start gap-2 rounded-xl bg-white px-3 py-2.5 text-left shadow-sm ring-1 ring-slate-200/80 hover:ring-[#2563eb]/40" @click="focusScan">
                        <span class="mt-0.5 text-[#2563eb]">▦</span>
                        <span>
                            <span class="block text-xs font-semibold text-slate-800">Scan Barcode</span>
                            <span class="block text-[10px] text-slate-400">Use barcode scanner</span>
                        </span>
                    </button>
                    <button type="button" class="flex items-start gap-2 rounded-xl bg-white px-3 py-2.5 text-left shadow-sm ring-1 ring-slate-200/80 hover:ring-[#2563eb]/40" @click="openCustomerDrawer">
                        <span class="mt-0.5 text-[#2563eb]">👤</span>
                        <span>
                            <span class="block text-xs font-semibold text-slate-800">{{ selectedCustomer ? 'Customer' : 'Add Customer' }}</span>
                            <span class="block truncate text-[10px] text-slate-400">{{ selectedCustomer?.name || 'Optional' }}</span>
                        </span>
                    </button>
                    <button type="button" class="flex items-start gap-2 rounded-xl bg-white px-3 py-2.5 text-left shadow-sm ring-1 ring-slate-200/80 hover:ring-[#2563eb]/40" @click="holdCart">
                        <span class="mt-0.5 text-[#2563eb]">⏸</span>
                        <span>
                            <span class="block text-xs font-semibold text-slate-800">Hold Sale</span>
                            <span class="block text-[10px] text-slate-400">Save for later</span>
                        </span>
                    </button>
                    <button type="button" class="flex items-start gap-2 rounded-xl bg-white px-3 py-2.5 text-left shadow-sm ring-1 ring-slate-200/80 hover:ring-[#2563eb]/40" @click="openCalculator">
                        <span class="mt-0.5 text-[#2563eb]">∑</span>
                        <span>
                            <span class="block text-xs font-semibold text-slate-800">Calculator</span>
                            <span class="block text-[10px] text-slate-400">F4 · quick math</span>
                        </span>
                    </button>
                    <div class="relative">
                        <button type="button" class="flex w-full items-start gap-2 rounded-xl bg-white px-3 py-2.5 text-left shadow-sm ring-1 ring-slate-200/80 hover:ring-[#2563eb]/40" @click="showMore = !showMore">
                            <span class="mt-0.5 text-[#2563eb]">⋯</span>
                            <span>
                                <span class="block text-xs font-semibold text-slate-800">More Actions</span>
                                <span class="block text-[10px] text-slate-400">Holds, orders, help</span>
                            </span>
                        </button>
                        <div v-if="showMore" class="absolute bottom-full left-0 z-20 mb-2 w-44 overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="showHolds = true; showMore = false">Held sales ({{ holds.length }})</button>
                            <Link :href="route('pos.orders.index')" class="block px-3 py-2 text-sm hover:bg-slate-50" @click="showMore = false">POS orders</Link>
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="showShortcuts = true; showMore = false">Shortcuts</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cart -->
            <aside class="flex min-h-0 flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="shrink-0 border-b border-slate-100 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Current Sale</h2>
                            <p class="text-[11px] text-slate-400">
                                {{ cart.length ? `${cart.length} item${cart.length === 1 ? '' : 's'} · qty ${cartItemCount}` : 'No items yet' }}
                            </p>
                        </div>
                        <button
                            v-if="cart.length"
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-semibold text-red-500 hover:bg-red-50"
                            @click="clearCart"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left transition hover:border-[#2563eb]/40 hover:bg-white"
                            @click="openCustomerDrawer"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#2563eb]/10 text-xs font-bold text-[#2563eb]">
                                {{ (selectedCustomer?.name || 'W').charAt(0).toUpperCase() }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800">
                                    {{ selectedCustomer?.name || 'Walk-in customer' }}
                                </span>
                                <span class="block truncate text-[11px] text-slate-400">
                                    {{ selectedCustomer?.phone || 'Tap to select or add' }}
                                </span>
                            </span>
                        </button>
                        <button
                            v-if="selectedCustomer"
                            type="button"
                            class="shrink-0 rounded-xl border border-slate-200 px-2.5 py-2 text-xs font-medium text-slate-500 hover:border-red-200 hover:text-red-500"
                            title="Remove customer"
                            @click="clearCustomer"
                        >
                            ×
                        </button>
                    </div>
                    <p v-if="holdNotice" class="mt-2 text-[11px] text-emerald-600">{{ holdNotice }}</p>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div v-if="cart.length" class="divide-y divide-slate-100">
                        <div
                            v-for="(line, index) in cart"
                            :key="`${line.product_id}-${index}`"
                            class="flex gap-3 px-4 py-3 transition"
                            :class="selectedIndex === index ? 'bg-[#eff6ff]' : 'hover:bg-slate-50/80'"
                            @click="selectLine(index)"
                        >
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100">
                                <img
                                    v-if="line.image_url"
                                    :src="line.image_url"
                                    :alt="line.name"
                                    class="max-h-10 max-w-10 object-contain"
                                />
                                <span v-else class="text-xs font-bold text-slate-400">{{ line.name?.charAt(0) }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ line.name }}</p>
                                        <p class="truncate text-[11px] text-slate-400">
                                            {{ money(line.unit_price) }}
                                            <span v-if="line.sku"> · {{ line.sku }}</span>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-0.5 shrink-0 rounded-md p-1 text-slate-300 hover:bg-red-50 hover:text-red-500"
                                        @click.stop="removeLine(index)"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <div class="inline-flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        <button
                                            type="button"
                                            class="px-2.5 py-1 text-sm font-medium text-slate-500 hover:bg-slate-50"
                                            @click.stop="bumpLineQty(index, -1)"
                                        >
                                            −
                                        </button>
                                        <span class="min-w-8 border-x border-slate-200 px-2 py-1 text-center text-sm font-semibold tabular-nums text-slate-800">
                                            {{ Number(line.quantity) }}
                                        </span>
                                        <button
                                            type="button"
                                            class="px-2.5 py-1 text-sm font-medium text-slate-500 hover:bg-slate-50"
                                            @click.stop="bumpLineQty(index, 1)"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <p class="text-sm font-bold tabular-nums text-slate-900">{{ money(lineNet(line)) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex h-full min-h-[12rem] flex-col items-center justify-center px-6 text-center">
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 7h14M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-600">Cart is empty</p>
                        <p class="mt-1 text-xs text-slate-400">Tap a product card to add it here.</p>
                    </div>
                </div>

                <div class="shrink-0 space-y-3 border-t border-slate-100 bg-slate-50/50 p-4">
                    <div class="flex items-center gap-2">
                        <div class="flex rounded-lg bg-white p-0.5 text-[11px] font-semibold ring-1 ring-slate-200">
                            <button
                                type="button"
                                class="rounded-md px-2 py-1"
                                :class="discountMode === 'percent' ? 'bg-slate-900 text-white' : 'text-slate-500'"
                                @click="discountMode = 'percent'"
                            >
                                %
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-2 py-1"
                                :class="discountMode === 'amount' ? 'bg-slate-900 text-white' : 'text-slate-500'"
                                @click="discountMode = 'amount'"
                            >
                                ৳
                            </button>
                        </div>
                        <input
                            v-model="discountDraft"
                            type="number"
                            min="0"
                            step="any"
                            class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#2563eb] focus:outline-none"
                            :placeholder="discountMode === 'percent' ? 'Discount %' : 'Discount ৳'"
                        />
                        <button
                            type="button"
                            class="rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:ring-[#2563eb]/40"
                            @click="applyCartDiscount"
                        >
                            Apply
                        </button>
                        <button
                            v-if="cartDiscountAmount > 0"
                            type="button"
                            class="rounded-xl px-2 py-2 text-sm text-slate-400 hover:text-red-500"
                            @click="clearCartDiscount"
                        >
                            ×
                        </button>
                    </div>

                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span class="tabular-nums text-slate-700">{{ money(cartSubtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-600">
                            <span>Discount</span>
                            <span class="tabular-nums">− {{ money(totalDiscount) }}</span>
                        </div>
                        <div class="flex items-end justify-between border-t border-slate-200/80 pt-2">
                            <span class="text-sm font-semibold text-slate-900">Total</span>
                            <span class="text-2xl font-bold tabular-nums text-slate-900">{{ money(grandTotal) }}</span>
                        </div>
                    </div>

                    <div v-if="showCashPanel && isCash" class="space-y-2 rounded-2xl border border-slate-200 bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cash tendered</p>
                        <input
                            v-model="form.amount_tendered"
                            type="number"
                            min="0"
                            step="any"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#2563eb] focus:outline-none"
                        />
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="amount in quickTenders"
                                :key="amount"
                                type="button"
                                class="rounded-lg bg-slate-50 px-2.5 py-1 text-xs font-medium ring-1 ring-slate-200 hover:ring-[#2563eb]"
                                @click="form.amount_tendered = Number(amount).toFixed(2)"
                            >
                                {{ money(amount) }}
                            </button>
                        </div>
                        <p class="text-sm text-slate-600">Change: <span class="font-semibold text-emerald-600">{{ money(changeDue) }}</span></p>
                        <button
                            type="button"
                            class="w-full rounded-xl bg-[#2563eb] py-3 text-sm font-semibold text-white hover:bg-[#1d4ed8] disabled:opacity-40"
                            :disabled="form.processing"
                            @click="complete"
                        >
                            Confirm cash payment
                        </button>
                    </div>

                    <button
                        v-else
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#2563eb] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!cart.length || form.processing"
                        @click="proceedToPayment"
                    >
                        <span>{{ form.processing ? 'Processing…' : 'Proceed to Payment' }}</span>
                        <span aria-hidden="true">→</span>
                    </button>
                    <InputError :message="form.errors.amount_tendered || form.errors.items || form.errors.payment_method" />

                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Payment Method</p>
                        <div class="grid grid-cols-5 gap-1.5">
                            <button
                                v-for="method in paymentMethods"
                                :key="method.value"
                                type="button"
                                class="flex flex-col items-center gap-1 rounded-xl border px-1 py-2 text-[10px] font-semibold transition"
                                :class="
                                    form.payment_method === method.value
                                        ? 'border-[#2563eb] bg-[#eff6ff] text-[#1d4ed8]'
                                        : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'
                                "
                                @click="form.payment_method = method.value"
                            >
                                <span class="text-sm leading-none">{{ paymentIcon(method.value) }}</span>
                                <span>{{ method.label }}</span>
                            </button>
                        </div>
                        <div v-if="!isCash" class="mt-2 space-y-1">
                            <label class="text-[11px] font-medium text-slate-500">{{ paymentReferenceLabel }}</label>
                            <input
                                v-model="form.payment_reference"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-[#2563eb] focus:outline-none focus:ring-2 focus:ring-[#2563eb]/20"
                                :placeholder="paymentReferenceLabel"
                            />
                            <InputError :message="form.errors.payment_reference" />
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Customer drawer -->
        <div
            v-if="showCustomerDrawer"
            class="fixed inset-0 z-40 flex justify-end bg-black/40 print:hidden"
            @click.self="showCustomerDrawer = false"
        >
            <div class="flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Customer</h3>
                        <p class="text-[11px] text-slate-400">Select existing or add new</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showCustomerDrawer = false">
                        ×
                    </button>
                </div>

                <div class="space-y-3 border-b border-slate-100 p-4">
                    <input
                        ref="customerSearchInput"
                        v-model="customerSearch"
                        type="search"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-[#2563eb] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2563eb]/20"
                        placeholder="Search by name, phone, code…"
                    />
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-xl py-2 text-sm font-semibold transition"
                            :class="!showNewCustomer ? 'bg-[#2563eb] text-white' : 'bg-slate-100 text-slate-600'"
                            @click="showNewCustomer = false"
                        >
                            Select
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-xl py-2 text-sm font-semibold transition"
                            :class="showNewCustomer ? 'bg-[#2563eb] text-white' : 'bg-slate-100 text-slate-600'"
                            @click="showNewCustomer = true"
                        >
                            + Add new
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <div v-if="!showNewCustomer" class="space-y-2">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl border px-3 py-3 text-left transition"
                            :class="!form.customer_id ? 'border-[#2563eb] bg-[#eff6ff]' : 'border-slate-200 hover:border-slate-300'"
                            @click="clearCustomer(); showCustomerDrawer = false"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">W</span>
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Walk-in customer</span>
                                <span class="block text-[11px] text-slate-400">No CRM profile</span>
                            </span>
                        </button>

                        <button
                            v-for="customer in filteredCustomers"
                            :key="customer.id"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl border px-3 py-3 text-left transition"
                            :class="String(form.customer_id) === String(customer.id) ? 'border-[#2563eb] bg-[#eff6ff]' : 'border-slate-200 hover:border-slate-300'"
                            @click="pickCustomer(customer)"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2563eb]/10 text-xs font-bold text-[#2563eb]">
                                {{ customer.name?.charAt(0)?.toUpperCase() || '?' }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-800">{{ customer.name }}</span>
                                <span class="block truncate text-[11px] text-slate-400">
                                    {{ [customer.phone, customer.code].filter(Boolean).join(' · ') || 'No phone' }}
                                </span>
                            </span>
                        </button>
                        <p v-if="!filteredCustomers.length" class="py-10 text-center text-sm text-slate-400">
                            No match. Use “Add new”.
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Name *</label>
                            <input
                                v-model="newCustomer.name"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#2563eb] focus:outline-none"
                                placeholder="Customer name"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Phone</label>
                            <input
                                v-model="newCustomer.phone"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#2563eb] focus:outline-none"
                                placeholder="01XXXXXXXXX"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Email</label>
                            <input
                                v-model="newCustomer.email"
                                type="email"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#2563eb] focus:outline-none"
                                placeholder="optional"
                            />
                        </div>
                        <p v-if="newCustomerError" class="text-xs text-red-500">{{ newCustomerError }}</p>
                        <button
                            type="button"
                            class="w-full rounded-xl bg-[#2563eb] py-3 text-sm font-semibold text-white disabled:opacity-40"
                            :disabled="newCustomerSaving"
                            @click="createCustomer"
                        >
                            {{ newCustomerSaving ? 'Saving…' : 'Save & select' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holds -->
        <div v-if="showHolds" class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 p-4 sm:items-center print:hidden" @click.self="showHolds = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Held sales</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-700" @click="showHolds = false">×</button>
                </div>
                <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">
                    <div v-for="hold in holds" :key="hold.id" class="rounded-xl border border-slate-100 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-slate-900">{{ hold.label }}</p>
                                <p class="text-[11px] text-slate-400">{{ formatDateTime(hold.savedAt) }} · {{ money(hold.total) }}</p>
                            </div>
                            <button type="button" class="text-xs text-red-500" @click="deleteHold(hold.id)">Delete</button>
                        </div>
                        <button type="button" class="mt-2 w-full rounded-xl bg-[#2563eb] py-2 text-xs font-semibold text-white" @click="recallHold(hold)">
                            Recall
                        </button>
                    </div>
                    <p v-if="!holds.length" class="py-8 text-center text-slate-400">No held sales.</p>
                </div>
            </div>
        </div>

        <div v-if="showShortcuts" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4 print:hidden" @click.self="showShortcuts = false">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 text-sm shadow-xl">
                <h3 class="font-semibold">Keyboard shortcuts</h3>
                <ul class="mt-3 space-y-2 text-slate-600">
                    <li><kbd>F2</kbd> — Focus search / barcode</li>
                    <li><kbd>F4</kbd> — Calculator</li>
                    <li><kbd>F6</kbd> — Hold sale</li>
                    <li><kbd>F7</kbd> — Open holds</li>
                    <li><kbd>F9</kbd> — Proceed / confirm payment</li>
                    <li><kbd>Esc</kbd> — Close overlays</li>
                </ul>
                <button type="button" class="mt-4 w-full rounded-xl bg-slate-100 py-2" @click="showShortcuts = false">Close</button>
            </div>
        </div>

        <div v-if="showCalculator" class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 p-4 sm:items-center print:hidden" @click.self="showCalculator = false">
            <div class="w-full max-w-xs rounded-2xl bg-white p-4 shadow-xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Calculator</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-700" @click="showCalculator = false">×</button>
                </div>
                <div class="mb-3 rounded-xl bg-slate-900 px-3 py-4 text-right font-mono text-2xl font-semibold text-white">
                    {{ calcDisplay }}
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" class="rounded-xl bg-slate-100 py-3 text-sm font-semibold" @click="resetCalculator">C</button>
                    <button type="button" class="rounded-xl bg-slate-100 py-3 text-sm font-semibold" @click="calcBackspace">⌫</button>
                    <button type="button" class="rounded-xl bg-slate-100 py-3 text-sm font-semibold" @click="calcPressOperator('/')">÷</button>
                    <button type="button" class="rounded-xl bg-slate-100 py-3 text-sm font-semibold" @click="calcPressOperator('*')">×</button>

                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(7)">7</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(8)">8</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(9)">9</button>
                    <button type="button" class="rounded-xl bg-slate-100 py-3 text-sm font-semibold" @click="calcPressOperator('-')">−</button>

                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(4)">4</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(5)">5</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(6)">6</button>
                    <button type="button" class="rounded-xl bg-slate-100 py-3 text-sm font-semibold" @click="calcPressOperator('+')">+</button>

                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(1)">1</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(2)">2</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(3)">3</button>
                    <button type="button" class="row-span-2 rounded-xl bg-[#2563eb] py-3 text-sm font-semibold text-white" @click="calcEquals">=</button>

                    <button type="button" class="col-span-2 rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDigit(0)">0</button>
                    <button type="button" class="rounded-xl bg-slate-50 py-3 text-sm font-semibold" @click="calcPressDot">.</button>
                </div>
                <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-xs font-semibold text-slate-700 hover:border-[#2563eb]/40 hover:text-[#2563eb]"
                    @click="useCalcAsTendered"
                >
                    Use as cash tendered
                </button>
            </div>
        </div>

        <!-- Receipt -->
        <div v-if="receipt" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 print:static print:bg-transparent print:p-0">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white text-slate-900 shadow-2xl print:max-w-[280px] print:rounded-none print:shadow-none">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 print:hidden">
                    <div>
                        <p class="text-sm font-semibold">Sale complete</p>
                        <p class="text-xs text-slate-500">{{ receipt.number }}</p>
                    </div>
                    <button type="button" class="text-slate-400" @click="closeReceipt">×</button>
                </div>
                <div class="receipt-thermal px-5 py-4 font-mono text-[12px]">
                    <p class="text-center text-sm font-bold">NexCore POS</p>
                    <p class="text-center">{{ receipt.register_name || register.name }}</p>
                    <p class="mt-2 text-center">{{ receipt.number }}</p>
                    <p class="text-center text-[11px] text-slate-500">{{ formatDateTime(receipt.completed_at || receipt.created_at) }}</p>
                    <hr class="my-2 border-dashed border-slate-300" />
                    <p>Customer: {{ receipt.customer_name || 'Walk-in' }}</p>
                    <p>Pay: {{ receipt.payment_method_label || receipt.payment_method }}</p>
                    <p v-if="receipt.payment_reference">Ref: {{ receipt.payment_reference }}</p>
                    <hr class="my-2 border-dashed border-slate-300" />
                    <div v-for="item in receipt.items" :key="item.id" class="mb-1">
                        <p class="font-semibold">{{ item.name }}</p>
                        <p class="flex justify-between">
                            <span>{{ Number(item.quantity).toFixed(2) }} × {{ Number(item.unit_price).toFixed(2) }}</span>
                            <span>{{ Number(item.line_total).toFixed(2) }}</span>
                        </p>
                    </div>
                    <hr class="my-2 border-dashed border-slate-300" />
                    <p v-if="Number(receipt.discount_total)" class="flex justify-between">
                        <span>Discount</span>
                        <span>−{{ Number(receipt.discount_total).toFixed(2) }}</span>
                    </p>
                    <p class="flex justify-between text-sm font-bold">
                        <span>TOTAL</span>
                        <span>{{ receipt.currency }} {{ Number(receipt.grand_total).toFixed(2) }}</span>
                    </p>
                    <p class="mt-3 text-center">Thank you</p>
                </div>
                <div class="flex gap-2 border-t border-slate-100 p-4 print:hidden">
                    <button type="button" class="flex-1 rounded-xl bg-slate-900 py-2.5 text-sm font-semibold text-white" @click="printReceipt">Print</button>
                    <button type="button" class="flex-1 rounded-xl bg-[#2563eb] py-2.5 text-sm font-semibold text-white" @click="closeReceipt">New sale</button>
                </div>
            </div>
        </div>
    </PosTerminalLayout>
</template>

<style>
@media print {
    body * { visibility: hidden !important; }
    .receipt-thermal,
    .receipt-thermal * { visibility: visible !important; }
    .receipt-thermal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 280px !important;
    }
}
</style>
