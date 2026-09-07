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
    if (stock.value === 'low') {
        return 'Low Stock';
    }
    if (stock.value === 'out') {
        return 'Out of Stock';
    }

    return 'Stock Overview';
});

const listRoute = computed(() => {
    if (stock.value === 'low') {
        return 'inventory.low-stock.index';
    }
    if (stock.value === 'out') {
        return 'inventory.out-of-stock.index';
    }

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

const setStock = (value) => {
    stock.value = value;
};
</script>

<template>
    <Head :title="title" />

    <AdminLayout :title="title">
        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <button
                type="button"
                class="admin-card border-l-4 border-l-amber-500 p-5 text-left transition hover:shadow-md"
                :class="stock === 'low' ? 'ring-1 ring-amber-200' : ''"
                @click="setStock('low')"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Low stock</p>
                <p class="mt-2 text-3xl font-semibold text-amber-700">{{ stats.low_stock }}</p>
            </button>
            <button
                type="button"
                class="admin-card border-l-4 border-l-red-500 p-5 text-left transition hover:shadow-md"
                :class="stock === 'out' ? 'ring-1 ring-red-200' : ''"
                @click="setStock('out')"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Out of stock</p>
                <p class="mt-2 text-3xl font-semibold text-red-700">{{ stats.out_of_stock }}</p>
            </button>
            <div class="admin-card border-l-4 border-l-brand-navy p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Total on hand</p>
                <p class="mt-2 text-3xl font-semibold text-brand-navy">
                    {{ Number(stats.on_hand_total || 0).toFixed(0) }}
                </p>
            </div>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                            :class="!stock ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            @click="setStock('')"
                        >
                            All
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                            :class="
                                stock === 'low' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                            @click="setStock('low')"
                        >
                            Low
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                            :class="
                                stock === 'out' ? 'bg-brand-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                            @click="setStock('out')"
                        >
                            Out
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ meta.total }} SKUs</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search SKU…" class="admin-data-table__search" />
                    <select v-model="warehouseId" class="admin-filter-select text-xs">
                        <option value="">All warehouses</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                    <Link
                        :href="route('inventory.stock-adjustment.index')"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                    >
                        Adjust stock
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
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in levels.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ row.product_name }}</div>
                                <div class="mt-0.5 font-mono text-xs text-gray-400">{{ row.sku || '—' }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.warehouse_name }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ Number(row.on_hand).toFixed(2) }}</td>
                            <td class="admin-data-table__cell tabular-nums text-gray-500">
                                {{ Number(row.reserved).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell tabular-nums font-medium text-brand-navy">
                                {{ Number(row.available).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="
                                        row.is_out
                                            ? 'bg-red-50 text-red-700'
                                            : row.is_low
                                              ? 'bg-amber-50 text-amber-800'
                                              : 'bg-emerald-50 text-emerald-700'
                                    "
                                >
                                    {{ row.is_out ? 'Out' : row.is_low ? 'Low' : 'OK' }}
                                    <span class="ml-1 text-[10px] opacity-70">rp {{ Number(row.reorder_point).toFixed(0) }}</span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!levels.data.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{
                                        search || warehouseId || stock
                                            ? 'No stock levels match these filters.'
                                            : 'No stock levels yet. Record an adjustment to start tracking.'
                                    }}
                                </p>
                                <Link
                                    v-if="!search && !warehouseId && !stock"
                                    :href="route('inventory.stock-adjustment.index')"
                                    class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                >
                                    Adjust stock
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="levels" :links="levels.links" />
            </div>
        </div>
    </AdminLayout>
</template>
