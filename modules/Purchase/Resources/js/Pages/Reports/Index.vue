<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    summary: { type: Object, required: true },
    by_status: { type: Object, default: () => ({}) },
    top_suppliers: { type: Array, default: () => [] },
    monthly: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Purchase Reports" />

    <AdminLayout title="Purchase Reports">
        <div class="mb-4">
            <Link :href="route('purchase.overview')" class="text-sm text-brand-navy hover:text-brand-orange">← Overview</Link>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <div class="text-xs uppercase text-gray-500">Spend</div>
                <div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.spend }}</div>
            </div>
            <div class="admin-card">
                <div class="text-xs uppercase text-gray-500">Paid</div>
                <div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.paid }}</div>
            </div>
            <div class="admin-card">
                <div class="text-xs uppercase text-gray-500">Outstanding</div>
                <div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.outstanding }}</div>
            </div>
            <div class="admin-card">
                <div class="text-xs uppercase text-gray-500">Returns</div>
                <div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.returns }}</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">By status</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Status</th>
                            <th class="pb-2 text-right">Orders</th>
                            <th class="pb-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, status) in by_status" :key="status" class="border-t border-gray-50">
                            <td class="py-2 capitalize">{{ status }}</td>
                            <td class="py-2 text-right">{{ row.count }}</td>
                            <td class="py-2 text-right">{{ row.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="admin-card">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Top suppliers</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Supplier</th>
                            <th class="pb-2 text-right">Orders</th>
                            <th class="pb-2 text-right">Spend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in top_suppliers" :key="row.id" class="border-t border-gray-50">
                            <td class="py-2">{{ row.name }}</td>
                            <td class="py-2 text-right">{{ row.orders_count }}</td>
                            <td class="py-2 text-right">{{ row.spend }}</td>
                        </tr>
                        <tr v-if="!top_suppliers.length">
                            <td colspan="3" class="py-8 text-center text-gray-500">No purchase data yet.</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="admin-card lg:col-span-2">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Monthly spend</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Period</th>
                            <th class="pb-2 text-right">Orders</th>
                            <th class="pb-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in monthly" :key="row.period" class="border-t border-gray-50">
                            <td class="py-2">{{ row.period }}</td>
                            <td class="py-2 text-right">{{ row.orders_count }}</td>
                            <td class="py-2 text-right">{{ row.amount }}</td>
                        </tr>
                        <tr v-if="!monthly.length">
                            <td colspan="3" class="py-8 text-center text-gray-500">No monthly activity.</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AdminLayout>
</template>
