<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

const cards = computed(() => [
    {
        label: 'Batches',
        value: props.stats.batches,
        href: 'inventory.batches.index',
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'Serials in stock',
        value: props.stats.serials_in_stock,
        href: 'inventory.serial-numbers.index',
        accent: 'border-l-emerald-500',
        valueClass: 'text-emerald-700',
    },
    {
        label: 'Low stock SKUs',
        value: props.stats.low_stock,
        href: 'inventory.low-stock.index',
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        label: 'On-hand total',
        value: props.stats.on_hand_total,
        href: 'inventory.stock-overview.index',
        accent: 'border-l-sky-500',
        valueClass: 'text-sky-700',
    },
]);

const links = [
    { label: 'Stock overview', href: 'inventory.stock-overview.index', desc: 'On-hand by warehouse' },
    { label: 'Low stock', href: 'inventory.low-stock.index', desc: 'Below reorder point' },
    { label: 'Out of stock', href: 'inventory.out-of-stock.index', desc: 'Zero available' },
    { label: 'Movements', href: 'inventory.stock-movement.index', desc: 'Full change log' },
    { label: 'Valuation', href: 'inventory.stock-valuation.index', desc: 'Retail × quantity' },
    { label: 'Batches', href: 'inventory.batches.index', desc: 'Lot tracking' },
    { label: 'Serial numbers', href: 'inventory.serial-numbers.index', desc: 'Unit tracking' },
    { label: 'Transfers', href: 'inventory.stock-transfer.index', desc: 'Between warehouses' },
];
</script>

<template>
    <Head title="Inventory reports" />

    <AdminLayout title="Inventory reports">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <p class="max-w-xl text-sm text-gray-500">
                Snapshot of tracking coverage and stock health. Open any card or shortcut for the live list.
            </p>
            <Link
                :href="route('inventory.stock-adjustment.index')"
                class="inline-flex items-center rounded-lg bg-brand-navy px-3.5 py-2 text-sm font-medium text-white hover:bg-brand-navy/90"
            >
                Adjust stock
            </Link>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="card in cards"
                :key="card.label"
                :href="route(card.href)"
                class="admin-card border-l-4 p-5 transition hover:border-brand-teal/30 hover:shadow-md"
                :class="card.accent"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight" :class="card.valueClass">{{ card.value }}</p>
            </Link>
        </div>

        <section class="admin-card">
            <h2 class="text-sm font-semibold text-brand-navy">Report shortcuts</h2>
            <ul class="mt-4 grid gap-1 sm:grid-cols-2">
                <li v-for="item in links" :key="item.href">
                    <Link
                        :href="route(item.href)"
                        class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-gray-50"
                    >
                        <span>
                            <span class="block text-sm font-medium text-brand-navy">{{ item.label }}</span>
                            <span class="block text-xs text-gray-400">{{ item.desc }}</span>
                        </span>
                        <span class="text-gray-300">→</span>
                    </Link>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
