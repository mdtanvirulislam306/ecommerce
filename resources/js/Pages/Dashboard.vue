<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Admin home. `role` selects the payload: `owner` or `sales_manager`.
 * Inventory, Accountant, and Ecommerce dashboards are later roles.
 */
const props = defineProps({
    role: { type: String, default: 'owner' },
    kpis: { type: Object, required: true },
    modulesAvailable: { type: Object, required: true },
    recentOrders: { type: Array, default: () => [] },
    recentOpenOrders: { type: Array, default: () => [] },
    lowStockItems: { type: Array, default: () => [] },
    quickLinks: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    pending: { class: 'bg-amber-50 text-amber-800' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const isSalesManager = computed(() => props.role === 'sales_manager');

const kpiCards = computed(() => isSalesManager.value ? salesManagerCards() : ownerCards());

const visibleOrders = computed(() => (isSalesManager.value ? props.recentOpenOrders : props.recentOrders));

function salesManagerCards() {
    const cards = [
        {
            key: 'open_orders',
            label: 'Open orders',
            value: props.kpis.open_orders,
            hint: 'Draft and pending only',
            route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
            query: {},
            accent: 'border-l-brand-teal',
            valueClass: 'text-brand-navy',
        },
        {
            key: 'draft_orders',
            label: 'Draft',
            value: props.kpis.draft_orders,
            hint: 'Not yet submitted',
            route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
            query: { status: 'draft' },
            accent: 'border-l-gray-300',
            valueClass: 'text-gray-700',
        },
        {
            key: 'pending_orders',
            label: 'Pending',
            value: props.kpis.pending_orders,
            hint: 'Waiting to confirm',
            route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
            query: { status: 'pending' },
            accent: 'border-l-amber-500',
            valueClass: 'text-amber-700',
        },
        {
            key: 'confirmed_orders',
            label: 'Confirmed',
            value: props.kpis.confirmed_orders,
            hint: 'Stock fulfilled',
            route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
            query: { status: 'confirmed' },
            accent: 'border-l-emerald-500',
            valueClass: 'text-emerald-700',
        },
        {
            key: 'revenue',
            label: 'Revenue',
            value: props.kpis.revenue,
            hint: 'Confirmed order totals',
            route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
            query: { status: 'confirmed' },
            accent: 'border-l-brand-navy',
            valueClass: 'text-brand-navy',
        },
    ];

    if (props.kpis.unpaid_invoices != null) {
        cards.push({
            key: 'unpaid_invoices',
            label: 'Unpaid invoices',
            value: props.kpis.unpaid_invoices,
            hint: 'Due, partial, and overdue',
            route: props.modulesAvailable.sales ? 'sales.invoices.all' : null,
            query: {},
            accent: 'border-l-amber-500',
            valueClass: 'text-amber-700',
        });
    }

    if (props.kpis.quotation_conversion_rate != null) {
        cards.push({
            key: 'quotation_conversion_rate',
            label: 'Quote conversion',
            value: props.kpis.quotation_conversion_rate,
            hint: 'Quotations linked to an order',
            route: props.modulesAvailable.sales ? 'sales.quotations.all' : null,
            query: {},
            accent: 'border-l-brand-teal',
            valueClass: 'text-brand-teal-dark',
        });
    }

    return cards;
}

function ownerCards() {
    return [
    {
        key: 'revenue',
        label: 'Revenue',
        value: props.kpis.revenue,
        hint: 'Confirmed order totals',
        route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
        query: { status: 'confirmed' },
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        key: 'orders',
        label: 'Orders',
        value: props.kpis.orders,
        hint: `${props.kpis.pending_orders} pending · excludes cancelled`,
        route: props.modulesAvailable.sales ? 'sales.orders.all' : null,
        query: {},
        accent: 'border-l-brand-teal',
        valueClass: 'text-brand-navy',
    },
    {
        key: 'low_stock',
        label: 'Low stock',
        value: props.modulesAvailable.inventory ? props.kpis.low_stock : '—',
        hint: props.modulesAvailable.inventory
            ? `${props.kpis.out_of_stock} out of stock`
            : 'Inventory is off',
        route: props.modulesAvailable.inventory ? 'inventory.low-stock.index' : null,
        query: {},
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        key: 'modules',
        label: 'Modules',
        value: props.kpis.modules,
        hint: 'Enabled for this shop',
        route: null,
        query: {},
        accent: 'border-l-brand-teal',
        valueClass: 'text-brand-teal-dark',
    },
    ];
}

const heading = computed(() => (isSalesManager.value ? 'Sales Manager overview' : 'Owner overview'));

const intro = computed(() => (
    isSalesManager.value
        ? 'Open orders are draft and pending. Confirmed revenue is counted separately.'
        : 'Confirmed sales revenue, open orders, and stock that needs a reorder.'
));

const ordersTitle = computed(() => (isSalesManager.value ? 'Recent open orders' : 'Recent orders'));

const ordersSubtitle = computed(() => {
    if (! isSalesManager.value) {
        return 'Latest sales activity';
    }

    return props.modulesAvailable.sales ? 'Draft and pending only' : 'Sales is off';
});

const emptyOrdersMessage = computed(() => {
    if (isSalesManager.value && ! props.modulesAvailable.sales) {
        return 'Sales is not enabled for this shop.';
    }

    if (isSalesManager.value) {
        return 'No open orders yet.';
    }

    return 'No orders yet.';
});
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout title="Dashboard">
        <div class="space-y-6" :data-dashboard-role="role">
            <div>
                <p class="text-sm text-gray-500">Welcome back, {{ user?.name }}</p>
                <h2 class="mt-1 text-xl font-semibold text-brand-navy">{{ heading }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                    {{ intro }}
                </p>
                <p
                    v-if="isSalesManager && !modulesAvailable.sales"
                    class="mt-3 text-sm text-amber-800"
                    data-sales-unavailable
                >
                    Sales is not enabled for this shop. Order, invoice, and quotation figures are unavailable.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <component
                    :is="card.route ? Link : 'div'"
                    v-for="card in kpiCards"
                    :key="card.key"
                    :href="card.route ? route(card.route, card.query) : undefined"
                    class="admin-card border-l-4 p-5"
                    :class="[card.accent, card.route ? 'transition hover:border-brand-teal/30 hover:shadow-md' : '']"
                >
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight" :class="card.valueClass">{{ card.value }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
                </component>
            </div>

            <div class="grid gap-6" :class="isSalesManager ? '' : 'lg:grid-cols-5'">
                <section class="admin-card !p-0 overflow-hidden" :class="isSalesManager ? '' : 'lg:col-span-3'">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">{{ ordersTitle }}</h2>
                            <p class="text-xs text-gray-400">
                                {{ ordersSubtitle }}
                            </p>
                        </div>
                        <Link
                            v-if="modulesAvailable.sales"
                            :href="route('sales.orders.all')"
                            class="text-xs font-medium text-brand-orange hover:underline"
                        >
                            View all →
                        </Link>
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
                                <tr v-for="order in visibleOrders" :key="order.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell">
                                        <Link
                                            v-if="!isSalesManager || modulesAvailable.sales"
                                            :href="route('sales.orders.show', order.id)"
                                            class="font-medium text-brand-navy hover:text-brand-orange"
                                        >
                                            {{ order.number }}
                                        </Link>
                                        <span v-else class="font-medium text-brand-navy">{{ order.number }}</span>
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
                                <tr v-if="!visibleOrders.length">
                                    <td colspan="5" class="px-5 py-12 text-center">
                                        <p class="text-sm text-gray-500">
                                            {{ emptyOrdersMessage }}
                                        </p>
                                        <Link
                                            v-if="modulesAvailable.sales"
                                            :href="route('sales.orders.create')"
                                            class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                        >
                                            Create first order
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section v-if="!isSalesManager" class="admin-card !p-0 overflow-hidden lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-brand-navy">Low stock</h2>
                            <p class="text-xs text-gray-400">
                                {{ modulesAvailable.inventory ? 'On hand still above zero' : 'Inventory is off' }}
                            </p>
                        </div>
                        <Link
                            v-if="modulesAvailable.inventory"
                            :href="route('inventory.low-stock.index')"
                            class="text-xs font-medium text-brand-orange hover:underline"
                        >
                            View all →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead v-if="modulesAvailable.inventory" class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Product</th>
                                    <th>On hand</th>
                                    <th>Reorder</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in lowStockItems" :key="item.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell">
                                        <p class="font-medium text-brand-navy">{{ item.product_name }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ item.sku || 'No SKU' }} · {{ item.warehouse_name }}
                                        </p>
                                    </td>
                                    <td class="admin-data-table__cell tabular-nums text-amber-700">{{ item.on_hand }}</td>
                                    <td class="admin-data-table__cell tabular-nums text-gray-500">{{ item.reorder_point }}</td>
                                </tr>
                                <tr v-if="!modulesAvailable.inventory">
                                    <td colspan="3" class="px-5 py-12 text-center">
                                        <p class="text-sm text-gray-500">Inventory is not enabled for this shop.</p>
                                        <p class="mt-1 text-xs text-gray-400">Stock levels are unavailable.</p>
                                    </td>
                                </tr>
                                <tr v-else-if="!lowStockItems.length">
                                    <td colspan="3" class="px-5 py-12 text-center">
                                        <p class="text-sm text-gray-500">No items are below their reorder point.</p>
                                        <Link
                                            v-if="kpis.out_of_stock > 0"
                                            :href="route('inventory.out-of-stock.index')"
                                            class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                        >
                                            {{ kpis.out_of_stock }} out of stock →
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section v-if="quickLinks.length" class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Shortcuts</h2>
                <p class="mt-0.5 text-xs text-gray-400">Jump into the shop you already run</p>
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
            <section v-else-if="isSalesManager" class="admin-card px-5 py-10 text-center">
                <h2 class="text-sm font-semibold text-brand-navy">Shortcuts</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ modulesAvailable.sales ? 'No sales shortcuts are available.' : 'Sales is not enabled for this shop.' }}
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
