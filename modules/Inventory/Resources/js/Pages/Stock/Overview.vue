<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    levels: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const warehouseId = ref(props.filters.warehouse_id ?? '');
const stock = ref(props.filters.stock ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.levels));

const title = computed(() => {
    if (stock.value === 'low') return 'Low Stock';
    if (stock.value === 'out') return 'Out of Stock';
    return 'Stock Overview';
});

const listRoute = computed(() => {
    if (stock.value === 'low') return 'inventory.low-stock.index';
    if (stock.value === 'out') return 'inventory.out-of-stock.index';
    return 'inventory.stock-overview.index';
});

const visitIndex = () => {
    const params = {
        search: search.value || undefined,
        warehouse_id: warehouseId.value || undefined,
        per_page: perPage.value,
    };

    if (listRoute.value === 'inventory.stock-overview.index' && stock.value) {
        params.stock = stock.value;
    }

    router.get(route(listRoute.value), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch([warehouseId, stock], visitIndex);
</script>

<template>
    <Head :title="title" />

    <AdminLayout :title="title">
        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Low stock</p>
                <p class="text-2xl font-semibold text-amber-700">{{ stats.low_stock }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Out of stock</p>
                <p class="text-2xl font-semibold text-red-700">{{ stats.out_of_stock }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Total on hand</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ Number(stats.on_hand_total || 0).toFixed(0) }}</p>
            </div>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('inventory.stock-overview.index')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="!stock ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600'"
                    >
                        All
                    </Link>
                    <Link
                        :href="route('inventory.low-stock.index')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="stock === 'low' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600'"
                    >
                        Low
                    </Link>
                    <Link
                        :href="route('inventory.out-of-stock.index')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="stock === 'out' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600'"
                    >
                        Out
                    </Link>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search SKU…" class="admin-data-table__search" />
                    <select v-model="warehouseId" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All warehouses</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                    <Link
                        :href="route('inventory.stock-adjustment.index')"
                        class="rounded-md bg-brand-navy px-3 py-1.5 text-xs font-medium text-white"
                    >
                        Adjust
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Product / SKU</th>
                            <th>Warehouse</th>
                            <th>On hand</th>
                            <th>Reserved</th>
                            <th>Available</th>
                            <th>Reorder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in levels.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ row.product_name }}</div>
                                <div class="text-xs text-gray-400">{{ row.sku || '—' }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.warehouse_name }}</td>
                            <td class="admin-data-table__cell">{{ Number(row.on_hand).toFixed(2) }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ Number(row.reserved).toFixed(2) }}</td>
                            <td class="admin-data-table__cell font-medium text-brand-navy">
                                {{ Number(row.available).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.is_out
                                            ? 'bg-red-50 text-red-700'
                                            : row.is_low
                                              ? 'bg-amber-50 text-amber-800'
                                              : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    {{ Number(row.reorder_point).toFixed(0) }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!levels.data.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">
                                No stock levels. Record an adjustment to start tracking.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="levels" :links="levels.links" />
            </div>
        </div>
    </AdminLayout>
</template>
