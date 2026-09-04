<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    orderStats: { type: Object, required: true },
    publicationStats: { type: Object, required: true },
    recentOrders: { type: Array, default: () => [] },
});

const statusMeta = {
    pending: { class: 'bg-amber-50 text-amber-800' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};
</script>

<template>
    <Head title="Ecommerce Overview" />

    <AdminLayout title="Ecommerce Overview">
        <p class="mb-5 text-sm text-gray-500">
            Storefront at <code>/shop</code>. Online orders use Commerce prices and Inventory stock; confirm posts accounting.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Published products</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ publicationStats.published }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Pending orders</p>
                <p class="text-2xl font-semibold text-amber-700">{{ orderStats.pending }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Confirmed</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ orderStats.confirmed }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Online revenue</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ orderStats.revenue }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="/shop" target="_blank" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">
                Open shop
            </a>
            <Link
                :href="route('ecommerce.online-orders.index')"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy"
            >
                Online orders
            </Link>
        </div>

        <section class="admin-card">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-brand-navy">Recent online orders</h2>
                <Link :href="route('ecommerce.online-orders.index')" class="text-xs text-brand-orange hover:underline">
                    View all →
                </Link>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Order</th>
                            <th class="pb-2">Customer</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Total</th>
                            <th class="pb-2">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in recentOrders" :key="order.id" class="border-t border-gray-50">
                            <td class="py-2">
                                <Link
                                    :href="route('ecommerce.online-orders.show', order.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ order.number }}
                                </Link>
                            </td>
                            <td class="py-2">{{ order.customer_name }}</td>
                            <td class="py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusMeta[order.status]?.class">
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td class="py-2">{{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}</td>
                            <td class="py-2 text-gray-500">{{ formatDateTime(order.created_at) }}</td>
                        </tr>
                        <tr v-if="!recentOrders.length">
                            <td colspan="5" class="py-8 text-center text-gray-500">No online orders yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
