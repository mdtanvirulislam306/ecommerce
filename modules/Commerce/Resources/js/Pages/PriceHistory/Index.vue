<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    history: { type: Object, required: true },
    priceLists: { type: Array, default: () => [] },
    actions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const priceListId = ref(props.filters.price_list_id ?? '');
const action = ref(props.filters.action ?? '');
const perPage = ref(props.filters.per_page ?? 25);

const meta = computed(() => paginationMeta(props.history));

const actionMeta = {
    created: { label: 'Created', class: 'bg-emerald-50 text-emerald-700' },
    updated: { label: 'Updated', class: 'bg-sky-50 text-sky-700' },
    deleted: { label: 'Deleted', class: 'bg-red-50 text-red-700' },
};

const formatPrice = (value) => (value !== null && value !== undefined ? Number(value).toFixed(2) : '—');

const priceChange = (row) => {
    if (row.action === 'created') {
        return formatPrice(row.new_price);
    }
    if (row.action === 'deleted') {
        return formatPrice(row.old_price);
    }
    return `${formatPrice(row.old_price)} → ${formatPrice(row.new_price)}`;
};

const visitIndex = () => {
    router.get(
        route('commerce.pricing.history.index'),
        {
            search: search.value || undefined,
            price_list_id: priceListId.value || undefined,
            action: action.value || undefined,
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

watch([priceListId, action], visitIndex);
</script>

<template>
    <Head title="Price History" />

    <AdminLayout title="Price History">
        <p class="mb-4 text-sm text-gray-500">
            Audit log of price list changes — created, updated, and removed tiers. New entries are recorded when prices are added or removed from a list.
        </p>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Change log</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search product or list…" class="admin-data-table__search" />
                    <select v-model="priceListId" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All price lists</option>
                        <option v-for="list in priceLists" :key="list.id" :value="list.id">
                            {{ list.name }}
                        </option>
                    </select>
                    <select v-model="action" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All actions</option>
                        <option v-for="a in actions" :key="a.value" :value="a.value">{{ a.label }}</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>When</th>
                            <th>Price list</th>
                            <th>Product</th>
                            <th>Tier qty</th>
                            <th>Price</th>
                            <th>Action</th>
                            <th>Changed by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in history.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ formatDateTime(row.created_at) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ row.price_list_name }}</div>
                                <div class="text-xs text-gray-400">{{ row.price_list_code }}</div>
                            </td>
                            <td class="admin-data-table__cell">
                                <div>{{ row.product_label }}</div>
                                <div v-if="row.product_sku" class="text-xs text-gray-400">{{ row.product_sku }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.min_quantity }}</td>
                            <td class="admin-data-table__cell font-medium text-brand-navy">
                                {{ row.currency }} {{ priceChange(row) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="actionMeta[row.action]?.class"
                                >
                                    {{ actionMeta[row.action]?.label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ row.changed_by_name || 'System' }}
                            </td>
                        </tr>
                        <tr v-if="history.data.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">
                                No price changes recorded yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="history" :links="history.links" />
            </div>
        </div>
    </AdminLayout>
</template>
