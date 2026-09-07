<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    by_status: { type: Object, default: () => ({}) },
    top_suppliers: { type: Array, default: () => [] },
    monthly: { type: Array, default: () => [] },
});

const cards = computed(() => [
    {
        label: 'Spend',
        value: props.summary.spend,
        href: 'purchase.orders.all',
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'Paid',
        value: props.summary.paid,
        href: 'purchase.payments',
        accent: 'border-l-emerald-500',
        valueClass: 'text-emerald-700',
    },
    {
        label: 'Outstanding',
        value: props.summary.outstanding,
        href: 'purchase.payments',
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        label: 'Returns',
        value: props.summary.returns,
        href: 'purchase.returns',
        accent: 'border-l-orange-500',
        valueClass: 'text-brand-orange',
    },
]);
</script>

<template>
    <Head title="Purchase Reports" />

    <AdminLayout title="Purchase Reports">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <p class="max-w-xl text-sm text-gray-500">
                Spend, payments, and supplier concentration. Open any card for the live list.
            </p>
            <Link
                :href="route('purchase.overview')"
                class="text-sm font-medium text-brand-navy hover:text-brand-orange"
            >
                ← Overview
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

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card !p-0 overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-brand-navy">By status</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-gray-200 bg-gray-50/90">
                            <tr class="admin-data-table__head">
                                <th>Status</th>
                                <th class="text-right">Orders</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, status) in by_status" :key="status" class="admin-data-table__row">
                                <td class="admin-data-table__cell capitalize">
                                    <Link
                                        :href="route('purchase.orders.all', { status })"
                                        class="font-medium text-brand-navy hover:text-brand-orange"
                                    >
                                        {{ status }}
                                    </Link>
                                </td>
                                <td class="admin-data-table__cell text-right tabular-nums">{{ row.count }}</td>
                                <td class="admin-data-table__cell text-right tabular-nums">{{ row.amount }}</td>
                            </tr>
                            <tr v-if="!Object.keys(by_status).length">
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">No status data.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card !p-0 overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-brand-navy">Top suppliers</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-gray-200 bg-gray-50/90">
                            <tr class="admin-data-table__head">
                                <th>Supplier</th>
                                <th class="text-right">Orders</th>
                                <th class="text-right">Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in top_suppliers" :key="row.id" class="admin-data-table__row">
                                <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.name }}</td>
                                <td class="admin-data-table__cell text-right tabular-nums">{{ row.orders_count }}</td>
                                <td class="admin-data-table__cell text-right tabular-nums">{{ row.spend }}</td>
                            </tr>
                            <tr v-if="!top_suppliers.length">
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">
                                    No purchase data yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card !p-0 overflow-hidden lg:col-span-2">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-brand-navy">Monthly spend</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-gray-200 bg-gray-50/90">
                            <tr class="admin-data-table__head">
                                <th>Period</th>
                                <th class="text-right">Orders</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in monthly" :key="row.period" class="admin-data-table__row">
                                <td class="admin-data-table__cell">{{ row.period }}</td>
                                <td class="admin-data-table__cell text-right tabular-nums">{{ row.orders_count }}</td>
                                <td class="admin-data-table__cell text-right tabular-nums">{{ row.amount }}</td>
                            </tr>
                            <tr v-if="!monthly.length">
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">
                                    No monthly activity.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
