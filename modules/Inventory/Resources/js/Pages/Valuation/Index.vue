<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
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
        <p class="mb-4 text-sm text-gray-500">Values use Retail list unit price × on-hand quantity.</p>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <select v-model="warehouseId" class="rounded-md border-gray-300 text-sm">
                <option value="">All warehouses</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <p class="text-lg font-semibold text-brand-navy">
                Total: {{ valuation.currency }} {{ valuation.total_value }}
            </p>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Product</th>
                        <th class="pb-2">Warehouse</th>
                        <th class="pb-2">On hand</th>
                        <th class="pb-2">Unit</th>
                        <th class="pb-2 text-right">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in valuation.rows" :key="i" class="border-t border-gray-50">
                        <td class="py-2 font-medium">{{ row.product_name }}</td>
                        <td class="py-2">{{ row.warehouse_name }}</td>
                        <td class="py-2">{{ row.on_hand }}</td>
                        <td class="py-2">{{ row.unit_cost }}</td>
                        <td class="py-2 text-right">{{ row.value }}</td>
                    </tr>
                    <tr v-if="!valuation.rows.length">
                        <td colspan="5" class="py-8 text-center text-gray-500">No stock levels.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AdminLayout>
</template>
