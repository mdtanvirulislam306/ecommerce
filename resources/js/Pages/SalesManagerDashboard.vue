<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Sales Manager overview. Props come from SalesManagerDashboardService.
 * Owner overview stays on Dashboard.vue.
 */
const props = defineProps({
    role: { type: String, default: 'sales_manager' },
    available: { type: Boolean, default: false },
    kpis: { type: Object, required: true },
    recentOrders: { type: Array, default: () => [] },
    unpaidInvoices: { type: Array, default: () => [] },
    quickLinks: { type: Array, default: () => [] },
});

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    pending: { class: 'bg-amber-50 text-amber-800' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const kpiCards = computed(() => {
    const orders = props.kpis.orders ?? {};
    const invoices = props.kpis.unpaid_invoices ?? {};
    const conversion = props.kpis.conversion ?? {};

    return [
        {
            key: 'open',
            label: 'Open orders',
            value: orders.open ?? 0,
            hint: `${orders.draft ?? 0} draft · ${orders.pending ?? 0} pending · ${orders.confirmed ?? 0} confirmed`,
        },
        {
            key: 'revenue',
            label: 'Confirmed revenue',
            value: props.kpis.revenue ?? '0.00',
            hint: 'Confirmed order totals',
        },
        {
            key: 'outstanding',
            label: 'Outstanding',
            value: invoices.outstanding ?? '0.00',
            hint: `${invoices.count ?? 0} unpaid · ${invoices.overdue ?? 0} overdue`,
        },
        {
            key: 'conversion',
            label: 'Quote conversion',
            value: conversion.rate === null || conversion.rate === undefined ? '—' : conversion.rate,
            hint: `${conversion.converted ?? 0} of ${conversion.total ?? 0} quotations`,
        },
    ];
});
</script>

<template>
    <AdminLayout title="Sales Manager">
        <Head title="Sales Manager" />

        <div class="space-y-6" :data-dashboard-role="role">
            <div>
                <h2 class="text-xl font-semibold text-brand-navy">Sales Manager</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                    Open orders, confirmed revenue, unpaid invoices, and quotation conversion.
                </p>
            </div>

            <div
                v-if="!available"
                class="admin-card border-l-4 border-l-amber-500 p-5"
                data-sales-unavailable
            >
                <p class="text-sm font-medium text-brand-navy">Sales is not enabled for this shop.</p>
                <p class="mt-1 text-sm text-gray-500">Order, invoice, and quotation figures are unavailable.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in kpiCards"
                    :key="card.key"
                    class="admin-card border-l-4 border-l-brand-navy p-5"
                >
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-brand-navy">{{ card.value }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="admin-card !p-0 overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-brand-navy">Recent orders</h2>
                        <p class="text-xs text-gray-400">Latest sales activity, including cancelled</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in recentOrders" :key="order.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell font-medium text-brand-navy">
                                        {{ order.number }}
                                    </td>
                                    <td class="admin-data-table__cell text-gray-600">{{ order.customer_name }}</td>
                                    <td class="admin-data-table__cell">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusMeta[order.status]?.class"
                                        >
                                            {{ order.status_label }}
                                        </span>
                                    </td>
                                    <td class="admin-data-table__cell font-medium tabular-nums">
                                        {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                                    </td>
                                    <td class="admin-data-table__cell text-gray-500">
                                        {{ formatDateTime(order.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!recentOrders.length">
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                        {{ available ? 'No orders yet.' : 'Orders are unavailable.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-card !p-0 overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-brand-navy">Unpaid invoices</h2>
                        <p class="text-xs text-gray-400">Due, partial, and overdue</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in unpaidInvoices" :key="invoice.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell font-medium text-brand-navy">
                                        {{ invoice.number }}
                                    </td>
                                    <td class="admin-data-table__cell text-gray-600">{{ invoice.customer_name }}</td>
                                    <td class="admin-data-table__cell font-medium tabular-nums">
                                        {{ invoice.currency }} {{ Number(invoice.amount_due).toFixed(2) }}
                                    </td>
                                </tr>
                                <tr v-if="!unpaidInvoices.length">
                                    <td colspan="3" class="px-5 py-12 text-center text-sm text-gray-500">
                                        {{ available ? 'No unpaid invoices.' : 'Invoices are unavailable.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section v-if="quickLinks.length" class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Shortcuts</h2>
                <ul class="mt-4 grid gap-1 sm:grid-cols-2">
                    <li v-for="link in quickLinks" :key="link.route">
                        <Link
                            :href="route(link.route)"
                            class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-gray-50"
                        >
                            <span>
                                <span class="block text-sm font-medium text-brand-navy">{{ link.label }}</span>
                                <span class="block text-xs text-gray-400">{{ link.description }}</span>
                            </span>
                            <span class="text-gray-300">→</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AdminLayout>
</template>
