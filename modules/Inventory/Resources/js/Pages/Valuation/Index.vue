<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    valuation: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const warehouseId = ref(props.filters.warehouse_id ?? '');

watch(warehouseId, (value) => {
    router.get(
        route('inventory.stock-valuation.index'),
        { warehouse_id: value || undefined },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Stock valuation" />

    <AdminLayout title="Stock valuation">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Valuation</h2>
                    <p class="text-xs text-gray-500">Retail list unit price × on-hand quantity</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <select v-model="warehouseId" class="admin-filter-select text-xs">
                        <option value="">All warehouses</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                    <div class="rounded-lg bg-brand-navy/5 px-3 py-2 text-sm font-semibold text-brand-navy">
                        {{ valuation.currency }} {{ valuation.total_value }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>On hand</th>
                            <th>Unit</th>
                            <th class="text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in valuation.rows" :key="i" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.product_name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.warehouse_name }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ row.on_hand }}</td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ row.unit_cost }}</td>
                            <td class="admin-data-table__cell text-right tabular-nums font-medium text-brand-navy">
                                {{ row.value }}
                            </td>
                        </tr>
                        <tr v-if="!valuation.rows.length">
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">No stock levels to value.</p>
                                <Link
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
        </div>
    </AdminLayout>
</template>
