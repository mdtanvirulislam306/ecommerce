<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.orders));

const statusMeta = {
    approved: 'bg-sky-50 text-sky-700',
    partial: 'bg-orange-50 text-brand-orange',
};

const visitIndex = () => {
    router.get(
        route('purchase.receive'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Purchase Receive" />

    <AdminLayout title="Purchase Receive">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Receivable purchase orders</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} approved / partial POs</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <Link
                        :href="route('purchase.orders.all')"
                        class="text-sm font-medium text-brand-navy hover:text-brand-orange"
                    >
                        All POs
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Lines</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ order.number }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ order.supplier_name }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusMeta[order.status] || 'bg-gray-100 text-gray-600'"
                                >
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell tabular-nums">
                                {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ order.items_count }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('purchase.orders.show', order.id)"
                                        class="admin-data-table__action"
                                        title="Receive"
                                    >
                                        <ActionIcon name="view" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No receivable orders match your search.' : 'No receivable orders.' }}
                                </p>
                                <Link
                                    v-if="!search"
                                    :href="route('purchase.orders.all', { status: 'approved' })"
                                    class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                >
                                    View approved POs
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
                <TablePagination :paginator="orders" :links="orders.links" />
            </div>
        </div>
    </AdminLayout>
</template>
