<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
});

const cards = [
    { key: 'warehouses', label: 'Active warehouses', href: 'inventory.warehouses.index' },
    { key: 'skus_tracked', label: 'SKUs tracked', href: 'inventory.stock-overview.index' },
    { key: 'on_hand_total', label: 'Total on hand', href: 'inventory.stock-overview.index' },
    { key: 'low_stock', label: 'Low stock', href: 'inventory.low-stock.index' },
    { key: 'out_of_stock', label: 'Out of stock', href: 'inventory.out-of-stock.index' },
    { key: 'movements_today', label: 'Movements today', href: 'inventory.stock-movement.index' },
];
</script>

<template>
    <Head title="Inventory Overview" />

    <AdminLayout title="Inventory Overview">
        <p class="mb-5 text-sm text-gray-500">
            Stock is movement-driven. Catalog owns product identity; Inventory owns warehouse quantities.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="card in cards"
                :key="card.key"
                :href="route(card.href)"
                class="admin-card transition hover:border-brand-orange/40"
            >
                <p class="text-xs text-gray-500">{{ card.label }}</p>
                <p class="mt-1 text-2xl font-semibold text-brand-navy">{{ stats[card.key] }}</p>
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Quick actions</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Link
                        :href="route('inventory.stock-adjustment.index')"
                        class="rounded-md bg-brand-navy px-3 py-1.5 text-xs font-medium text-white"
                    >
                        Adjust stock
                    </Link>
                    <Link
                        :href="route('inventory.warehouses.index')"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-brand-navy"
                    >
                        Manage warehouses
                    </Link>
                    <Link
                        :href="route('inventory.stock-movement.index')"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-brand-navy"
                    >
                        View movements
                    </Link>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Warehouses</h2>
                <ul v-if="warehouses.length" class="mt-3 space-y-2 text-sm">
                    <li v-for="wh in warehouses" :key="wh.id" class="flex justify-between gap-3">
                        <span class="text-brand-navy">{{ wh.name }}</span>
                        <span class="text-gray-400">
                            {{ wh.code }}
                            <span v-if="wh.is_default" class="ml-1 text-brand-orange">default</span>
                        </span>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-gray-500">No warehouses yet. Create one to start tracking stock.</p>
            </section>
        </div>
    </AdminLayout>
</template>
