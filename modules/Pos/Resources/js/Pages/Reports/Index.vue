<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    summary: { type: Object, required: true },
    monthly: { type: Array, default: () => [] },
    top_registers: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="POS Reports" />
    <AdminLayout title="POS Reports">
        <div class="mb-4"><Link :href="route('pos.terminal')" class="text-sm text-brand-navy hover:text-brand-orange">← Terminal</Link></div>
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="admin-card"><div class="text-xs uppercase text-gray-500">Revenue today</div><div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.revenue_today }}</div></div>
            <div class="admin-card"><div class="text-xs uppercase text-gray-500">Orders today</div><div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.orders_today }}</div></div>
            <div class="admin-card"><div class="text-xs uppercase text-gray-500">Open sessions</div><div class="mt-1 text-2xl font-semibold text-brand-navy">{{ summary.open_sessions }}</div></div>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Monthly sales</h2>
                <table class="min-w-full text-sm">
                    <tbody>
                        <tr v-for="row in monthly" :key="row.period" class="border-t border-gray-50">
                            <td class="py-2">{{ row.period }}</td>
                            <td class="py-2 text-right">{{ row.orders_count }}</td>
                            <td class="py-2 text-right">{{ row.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <section class="admin-card">
                <h2 class="mb-3 text-sm font-semibold text-brand-navy">Top registers</h2>
                <table class="min-w-full text-sm">
                    <tbody>
                        <tr v-for="row in top_registers" :key="row.name" class="border-t border-gray-50">
                            <td class="py-2">{{ row.name }}</td>
                            <td class="py-2 text-right">{{ row.orders_count }}</td>
                            <td class="py-2 text-right">{{ row.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AdminLayout>
</template>
