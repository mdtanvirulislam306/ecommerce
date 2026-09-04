<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    register: { type: Object, required: true },
    sessionOpen: { type: Boolean, default: false },
    products: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const query = ref('');
const results = ref([...props.products]);
const cart = ref([]);

const form = useForm({
    pos_register_id: props.register.id,
    customer_name: 'Walk-in',
    amount_tendered: '',
    items: [],
});

const subtotal = computed(() =>
    cart.value.reduce((sum, line) => sum + Number(line.unit_price) * Number(line.quantity), 0),
);

const currency = computed(() => cart.value[0]?.currency || 'BDT');

watch(subtotal, (value) => {
    if (!form.amount_tendered || Number(form.amount_tendered) < value) {
        form.amount_tendered = value ? value.toFixed(2) : '';
    }
});

let searchTimer = null;
watch(query, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        if (!value) {
            results.value = [...props.products];
            return;
        }
        const res = await fetch(`${route('pos.terminal.search')}?q=${encodeURIComponent(value)}`);
        const data = await res.json();
        results.value = Array.isArray(data) ? data : (data.results ?? []);
    }, 250);
});

const onScanEnter = async () => {
    const code = query.value?.trim();
    if (!code) return;

    const res = await fetch(`${route('pos.terminal.search')}?q=${encodeURIComponent(code)}`);
    const data = await res.json();
    const exact = data.exact ?? null;
    results.value = Array.isArray(data) ? data : (data.results ?? []);

    if (exact) {
        addProduct(exact);
        query.value = '';
        results.value = [...props.products];
    }
};

const addProduct = (product) => {
    if (!product.price || !product.in_stock) return;
    const existing = cart.value.find((line) => line.product_id === product.id);
    if (existing) {
        existing.quantity = Number(existing.quantity) + 1;
        return;
    }
    cart.value.push({
        product_id: product.id,
        product_variant_id: null,
        name: product.name,
        sku: product.sku,
        quantity: 1,
        unit_price: product.price,
        currency: product.currency,
    });
};

const removeLine = (index) => {
    cart.value.splice(index, 1);
};

const complete = () => {
    form.items = cart.value.map((line) => ({
        product_id: line.product_id,
        product_variant_id: line.product_variant_id,
        quantity: line.quantity,
    }));
    form.post(route('pos.terminal.complete'), {
        onSuccess: () => {
            cart.value = [];
            form.reset('amount_tendered', 'items');
            form.customer_name = 'Walk-in';
            form.pos_register_id = props.register.id;
        },
    });
};
</script>

<template>
    <Head title="POS Terminal" />

    <AdminLayout title="POS Terminal">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm">
            <div>
                <span class="font-medium text-brand-navy">{{ register.name }}</span>
                <span class="ml-2 text-gray-500">({{ register.code }})</span>
                <span
                    class="ml-3 rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="sessionOpen ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                >
                    {{ sessionOpen ? 'Session open' : 'Will auto-open on first sale' }}
                </span>
            </div>
            <div class="flex gap-3 text-xs text-gray-500">
                <span>Today: {{ stats.completed_today }} sales</span>
                <span>Revenue: {{ stats.revenue_today }}</span>
                <Link :href="route('pos.orders.index')" class="text-brand-orange hover:underline">Orders →</Link>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="admin-card space-y-3">
                <input
                    v-model="query"
                    type="search"
                    placeholder="Scan barcode or search name / SKU…"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    autofocus
                    @keydown.enter.prevent="onScanEnter"
                />
                <p class="text-[11px] text-gray-500">Press Enter after a barcode scan to add the exact match.</p>
                <div class="max-h-[28rem] space-y-2 overflow-y-auto">
                    <button
                        v-for="product in results"
                        :key="product.id"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-left text-sm hover:border-brand-navy/30 hover:bg-gray-50 disabled:opacity-40"
                        :disabled="!product.price || !product.in_stock"
                        @click="addProduct(product)"
                    >
                        <span>
                            <span class="font-medium text-brand-navy">{{ product.name }}</span>
                            <span class="ml-2 text-xs text-gray-500">{{ product.sku }}</span>
                        </span>
                        <span class="text-right">
                            <span v-if="product.price" class="font-medium">
                                {{ product.currency }} {{ Number(product.price).toFixed(2) }}
                            </span>
                            <span v-else class="text-xs text-gray-400">No price</span>
                            <span class="block text-[11px]" :class="product.in_stock ? 'text-emerald-700' : 'text-red-600'">
                                {{ product.in_stock ? `Stock ${Number(product.stock_available).toFixed(0)}` : 'Out' }}
                            </span>
                        </span>
                    </button>
                </div>
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Current sale</h2>
                <div>
                    <label class="text-xs text-gray-500">Customer</label>
                    <TextInput v-model="form.customer_name" class="mt-1 block w-full" />
                </div>

                <div class="max-h-64 space-y-2 overflow-y-auto">
                    <div
                        v-for="(line, index) in cart"
                        :key="`${line.product_id}-${index}`"
                        class="flex items-center justify-between gap-2 rounded border border-gray-100 px-3 py-2 text-sm"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-brand-navy">{{ line.name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ line.currency }} {{ Number(line.unit_price).toFixed(2) }}
                            </p>
                        </div>
                        <input
                            v-model.number="line.quantity"
                            type="number"
                            min="1"
                            step="1"
                            class="w-16 rounded border-gray-200 text-sm"
                        />
                        <p class="w-20 text-right font-medium">
                            {{ (Number(line.unit_price) * Number(line.quantity)).toFixed(2) }}
                        </p>
                        <button type="button" class="text-xs text-red-600" @click="removeLine(index)">×</button>
                    </div>
                    <p v-if="!cart.length" class="py-8 text-center text-sm text-gray-500">Tap products to add.</p>
                </div>

                <div class="border-t border-gray-100 pt-3 space-y-2">
                    <div class="flex justify-between text-base font-semibold text-brand-navy">
                        <span>Total</span>
                        <span>{{ currency }} {{ subtotal.toFixed(2) }}</span>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Cash tendered</label>
                        <TextInput v-model="form.amount_tendered" type="number" min="0" step="any" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.amount_tendered" />
                    </div>
                    <p class="text-sm text-gray-600">
                        Change:
                        {{ currency }}
                        {{ Math.max(0, Number(form.amount_tendered || 0) - subtotal).toFixed(2) }}
                    </p>
                    <InputError :message="form.errors.items" />
                    <PrimaryButton
                        type="button"
                        class="w-full justify-center"
                        :disabled="!cart.length || form.processing"
                        @click="complete"
                    >
                        Complete cash sale
                    </PrimaryButton>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
