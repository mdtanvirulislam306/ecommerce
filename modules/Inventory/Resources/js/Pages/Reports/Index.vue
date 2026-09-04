<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Inventory reports" />
    <AdminLayout title="Inventory reports">
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Batches</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.batches }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Serials in stock</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.serials_in_stock }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Low stock SKUs</p>
                <p class="text-2xl font-semibold text-brand-orange">{{ stats.low_stock }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">On-hand total</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.on_hand_total }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <Link :href="route('inventory.batches.index')" class="rounded-md bg-brand-navy px-3 py-2 text-white">Batches</Link>
            <Link :href="route('inventory.serial-numbers.index')" class="rounded-md border px-3 py-2">Serials</Link>
            <Link :href="route('inventory.stock-valuation.index')" class="rounded-md border px-3 py-2">Valuation</Link>
            <Link :href="route('inventory.stock-overview.index')" class="rounded-md border px-3 py-2">Stock overview</Link>
        </div>
    </AdminLayout>
</template>
