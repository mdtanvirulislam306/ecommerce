<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import AdminDateRangePicker from '@/Components/Admin/AdminDateRangePicker.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.orders));

const statusMeta = {
    completed: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    cancelled: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const money = (currency, value) =>
    `${currency || 'BDT'} ${Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const visitIndex = (page = 1) => {
    router.get(
        route('pos.orders.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: perPage.value,
            page: page > 1 ? page : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const setStatus = (value) => {
    status.value = value;
};

const applyDateRange = ({ from, to }) => {
    dateFrom.value = from || '';
    dateTo.value = to || '';
    visitIndex();
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => visitIndex(), 300);
});
watch(status, () => visitIndex());
</script>

<template>
    <Head title="POS Orders" />

    <AdminLayout title="POS Orders">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">POS orders</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminDateRangePicker
                        :from="dateFrom"
                        :to="dateTo"
                        placeholder="Created date"
                        @update="applyDateRange"
                    />
                    <input v-model="search" type="search" placeholder="Search order or customer…" class="admin-data-table__search" />
                    <Link
                        :href="route('pos.terminal')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-3 py-2 text-xs font-semibold text-white hover:bg-brand-orange-dark"
                    >
                        <ActionIcon name="terminal" />
                        Terminal
                    </Link>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 border-b border-gray-100 px-4 py-2.5 sm:px-5">
                <button
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[11px] font-medium transition ring-1"
                    :class="
                        !status
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                    "
                    @click="setStatus('')"
                >
                    All
                </button>
                <button
                    v-for="opt in statusOptions"
                    :key="opt.value"
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[11px] font-medium transition ring-1"
                    :class="
                        status === opt.value
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                    "
                    @click="setStatus(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('pos.orders.show', order.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ order.number }}
                                </Link>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ order.customer_name || 'Walk-in' }}</td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ order.items_count }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusMeta[order.status]?.class"
                                >
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell tabular-nums font-medium">
                                {{ money(order.currency, order.grand_total) }}
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ formatDateTime(order.created_at) }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('pos.orders.show', order.id)"
                                        class="admin-data-table__action"
                                        title="View"
                                    >
                                        <ActionIcon name="view" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">
                                {{ search || status || dateFrom || dateTo ? 'No orders match these filters.' : 'No POS sales yet.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex()">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="orders" :links="orders.links" />
            </div>
        </div>
    </AdminLayout>
</template>
