<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    movements: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const warehouseId = ref(props.filters.warehouse_id ?? '');
const type = ref(props.filters.type ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.movements));

const typeBadge = (value) => {
    const key = String(value || '').toLowerCase();
    if (key.includes('in') || key.includes('purchase') || key.includes('return')) {
        return 'bg-emerald-50 text-emerald-700';
    }
    if (key.includes('out') || key.includes('sale') || key.includes('damage')) {
        return 'bg-red-50 text-red-700';
    }
    if (key.includes('transfer')) {
        return 'bg-sky-50 text-sky-700';
    }

    return 'bg-gray-100 text-gray-700';
};

const visitIndex = () => {
    router.get(
        route('inventory.stock-movement.index'),
        {
            search: search.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            type: type.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch([warehouseId, type], visitIndex);
</script>

<template>
    <Head title="Stock Movement" />

    <AdminLayout title="Stock Movement">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Movement log</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} entries · adjustments, sales, transfers</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <select v-model="warehouseId" class="admin-filter-select text-xs">
                        <option value="">All warehouses</option>
                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                    </select>
                    <select v-model="type" class="admin-filter-select text-xs">
                        <option value="">All types</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <Link
                        :href="route('inventory.stock-adjustment.index')"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                    >
                        Adjust
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>When</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Before → After</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in movements.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell whitespace-nowrap text-sm text-gray-500">
                                {{ formatDateTime(row.created_at) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ row.product_name }}</div>
                                <div class="mt-0.5 font-mono text-xs text-gray-400">{{ row.sku || '—' }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.warehouse_name }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="typeBadge(row.type || row.type_label)"
                                >
                                    {{ row.type_label }}
                                </span>
                            </td>
                            <td
                                class="admin-data-table__cell tabular-nums font-medium"
                                :class="Number(row.quantity) >= 0 ? 'text-emerald-700' : 'text-red-600'"
                            >
                                {{ Number(row.quantity) > 0 ? '+' : '' }}{{ Number(row.quantity).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell tabular-nums text-sm text-gray-600">
                                {{ Number(row.quantity_before).toFixed(2) }} → {{ Number(row.quantity_after).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ row.created_by_name || 'System' }}
                            </td>
                        </tr>
                        <tr v-if="!movements.data.length">
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{
                                        search || warehouseId || type
                                            ? 'No movements match these filters.'
                                            : 'No movements yet.'
                                    }}
                                </p>
                                <div v-if="!search && !warehouseId && !type" class="mt-3 flex justify-center gap-4">
                                    <Link
                                        :href="route('inventory.stock-adjustment.index')"
                                        class="text-sm font-medium text-brand-orange hover:underline"
                                    >
                                        Adjust stock
                                    </Link>
                                    <Link
                                        :href="route('inventory.stock-transfer.create')"
                                        class="text-sm font-medium text-brand-navy hover:underline"
                                    >
                                        New transfer
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="movements" :links="movements.links" />
            </div>
        </div>
    </AdminLayout>
</template>
