<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
    recentOrders: { type: Array, default: () => [] },
});

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    pending: { class: 'bg-amber-50 text-amber-800' },
    approved: { class: 'bg-sky-50 text-sky-700' },
    partial: { class: 'bg-orange-50 text-brand-orange' },
    received: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const kpiCards = computed(() => [
    {
        label: 'Suppliers',
        value: props.stats.suppliers,
        hint: 'Active vendors',
        href: 'purchase.suppliers.all',
        query: {},
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'Pending approval',
        value: props.stats.pending,
        hint: 'Needs review',
        href: 'purchase.orders.all',
        query: { status: 'pending' },
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        label: 'To receive',
        value: Number(props.stats.approved || 0) + Number(props.stats.partial || 0),
        hint: 'Approved / partial',
        href: 'purchase.receive',
        query: {},
        accent: 'border-l-sky-500',
        valueClass: 'text-sky-700',
    },
    {
        label: 'Fully received',
        value: props.stats.received,
        hint: 'In inventory',
        href: 'purchase.orders.all',
        query: { status: 'received' },
        accent: 'border-l-emerald-500',
        valueClass: 'text-emerald-700',
    },
]);

const primaryActions = [
    { label: 'New purchase order', href: 'purchase.orders.create', primary: true },
    { label: 'Receive goods', href: 'purchase.receive', primary: false },
    { label: 'Suppliers', href: 'purchase.suppliers.all', primary: false },
];

const secondaryActions = [
    { label: 'All POs', href: 'purchase.orders.all', desc: 'Browse & filter orders' },
    { label: 'Payments', href: 'purchase.payments', desc: 'Supplier settlements' },
    { label: 'Returns', href: 'purchase.returns', desc: 'Goods returned' },
    { label: 'Reports', href: 'purchase.reports', desc: 'Spend & outstanding' },
];
</script>

<template>
    <Head title="Purchase Overview" />

    <AdminLayout title="Purchase Overview">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-xl">
                <p class="text-sm leading-relaxed text-gray-500">
                    Purchase orders bring stock in. Receiving a PO writes Inventory purchase_receive movements.
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

        <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="card in kpiCards"
                :key="card.label"
                :href="route(card.href, card.query || {})"
                class="admin-card border-l-4 p-5 transition hover:border-brand-teal/30 hover:shadow-md"
                :class="card.accent"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight" :class="card.valueClass">{{ card.value }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
            </Link>
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-5">
            <section class="admin-card lg:col-span-3 !p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Recent POs</h2>
                        <p class="text-xs text-gray-400">Latest purchase activity</p>
                    </div>
                    <Link :href="route('purchase.orders.all')" class="text-xs font-medium text-brand-orange hover:underline">
                        View all →
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-gray-200 bg-gray-50/90">
                            <tr class="admin-data-table__head">
                                <th>PO</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in recentOrders" :key="order.id" class="admin-data-table__row">
                                <td class="admin-data-table__cell">
                                    <Link
                                        :href="route('purchase.orders.show', order.id)"
                                        class="font-medium text-brand-navy hover:text-brand-orange"
                                    >
                                        {{ order.number }}
                                    </Link>
                                </td>
                                <td class="admin-data-table__cell text-gray-600">{{ order.supplier_name }}</td>
                                <td class="admin-data-table__cell">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="statusMeta[order.status]?.class"
                                    >
                                        {{ order.status_label }}
                                    </span>
                                </td>
                                <td class="admin-data-table__cell tabular-nums">
                                    {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                                </td>
                                <td class="admin-data-table__cell text-gray-500">
                                    {{ formatDateTime(order.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="!recentOrders.length">
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <p class="text-sm text-gray-500">No purchase orders yet.</p>
                                    <Link
                                        :href="route('purchase.orders.create')"
                                        class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                    >
                                        Create first PO
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card lg:col-span-2">
                <h2 class="text-sm font-semibold text-brand-navy">Shortcuts</h2>
                <p class="mt-0.5 text-xs text-gray-400">Common purchase workflows</p>
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
