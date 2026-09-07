<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
});

const kpiCards = computed(() => [
    {
        label: 'Warehouses',
        value: props.stats.warehouses,
        hint: 'Active locations',
        href: 'inventory.warehouses.index',
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'SKUs tracked',
        value: props.stats.skus_tracked,
        hint: 'With stock levels',
        href: 'inventory.stock-overview.index',
        accent: 'border-l-brand-teal',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'On hand',
        value: Number(props.stats.on_hand_total || 0).toFixed(0),
        hint: 'Total quantity',
        href: 'inventory.stock-overview.index',
        accent: 'border-l-emerald-500',
        valueClass: 'text-emerald-700',
    },
    {
        label: 'Low stock',
        value: props.stats.low_stock,
        hint: 'Needs reorder',
        href: 'inventory.low-stock.index',
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        label: 'Out of stock',
        value: props.stats.out_of_stock,
        hint: 'Zero available',
        href: 'inventory.out-of-stock.index',
        accent: 'border-l-red-500',
        valueClass: 'text-red-700',
    },
    {
        label: 'Movements today',
        value: props.stats.movements_today,
        hint: 'Quantity changes',
        href: 'inventory.stock-movement.index',
        accent: 'border-l-sky-500',
        valueClass: 'text-sky-700',
    },
]);

const primaryActions = [
    { label: 'Adjust stock', href: 'inventory.stock-adjustment.index', primary: true },
    { label: 'New transfer', href: 'inventory.stock-transfer.create', primary: false },
    { label: 'Stock overview', href: 'inventory.stock-overview.index', primary: false },
];

const secondaryActions = [
    { label: 'Warehouses', href: 'inventory.warehouses.index', desc: 'Locations & defaults' },
    { label: 'Movements', href: 'inventory.stock-movement.index', desc: 'Full change log' },
    { label: 'Transfers', href: 'inventory.stock-transfer.index', desc: 'Between warehouses' },
    { label: 'Batches', href: 'inventory.batches.index', desc: 'Lot tracking' },
    { label: 'Serials', href: 'inventory.serial-numbers.index', desc: 'Unit tracking' },
    { label: 'Valuation', href: 'inventory.stock-valuation.index', desc: 'Retail × on-hand' },
];
</script>

<template>
    <Head title="Inventory Overview" />

    <AdminLayout title="Inventory Overview">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-xl">
                <p class="text-sm leading-relaxed text-gray-500">
                    Stock is movement-driven. Catalog owns product identity; Inventory owns warehouse quantities.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="action in primaryActions"
                    :key="action.href"
                    :href="route(action.href)"
                    class="inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-medium transition"
                    :class="
                        action.primary
                            ? 'bg-brand-navy text-white hover:bg-brand-navy/90'
                            : 'border border-gray-200 bg-white text-brand-navy hover:border-brand-teal/40 hover:bg-gray-50'
                    "
                >
                    {{ action.label }}
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="card in kpiCards"
                :key="card.label"
                :href="route(card.href)"
                class="admin-card border-l-4 p-5 transition hover:border-brand-teal/30 hover:shadow-md"
                :class="card.accent"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight" :class="card.valueClass">{{ card.value }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
            </Link>
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-5">
            <section class="admin-card lg:col-span-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Warehouses</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Active stock locations</p>
                    </div>
                    <Link
                        :href="route('inventory.warehouses.index')"
                        class="text-xs font-medium text-brand-orange hover:underline"
                    >
                        Manage →
                    </Link>
                </div>

                <ul v-if="warehouses.length" class="mt-5 divide-y divide-gray-100">
                    <li
                        v-for="wh in warehouses"
                        :key="wh.id"
                        class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                    >
                        <div>
                            <p class="text-sm font-medium text-brand-navy">{{ wh.name }}</p>
                            <p class="text-xs text-gray-400">{{ wh.code }}</p>
                        </div>
                        <span
                            v-if="wh.is_default"
                            class="inline-flex rounded-full bg-brand-orange/10 px-2.5 py-0.5 text-xs font-medium text-brand-orange"
                        >
                            Default
                        </span>
                        <span
                            v-else
                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="wh.is_active !== false ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ wh.is_active !== false ? 'Active' : 'Inactive' }}
                        </span>
                    </li>
                </ul>
                <div v-else class="mt-5 rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center">
                    <p class="text-sm text-gray-500">No warehouses yet. Create one to start tracking stock.</p>
                    <Link
                        :href="route('inventory.warehouses.index')"
                        class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                    >
                        Add warehouse
                    </Link>
                </div>
            </section>

            <section class="admin-card lg:col-span-2">
                <h2 class="text-sm font-semibold text-brand-navy">Shortcuts</h2>
                <p class="mt-0.5 text-xs text-gray-400">Jump into common inventory work</p>
                <ul class="mt-4 space-y-1">
                    <li v-for="action in secondaryActions" :key="action.href">
                        <Link
                            :href="route(action.href)"
                            class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-gray-50"
                        >
                            <span>
                                <span class="block text-sm font-medium text-brand-navy">{{ action.label }}</span>
                                <span class="block text-xs text-gray-400">{{ action.desc }}</span>
                            </span>
                            <span class="text-gray-300">→</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AdminLayout>
</template>
