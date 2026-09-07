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
    sessions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.sessions));

const money = (value) => Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const visit = (page = 1) => {
    router.get(
        route('pos.open-sessions'),
        {
            search: search.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: perPage.value,
            page: page > 1 ? page : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const applyDateRange = ({ from, to }) => {
    dateFrom.value = from || '';
    dateTo.value = to || '';
    visit();
};

let t = null;
watch(search, () => {
    clearTimeout(t);
    t = setTimeout(() => visit(), 300);
});
</script>

<template>
    <Head title="Open Sessions" />

    <AdminLayout title="Open Sessions">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Open POS sessions</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} open</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminDateRangePicker
                        :from="dateFrom"
                        :to="dateTo"
                        placeholder="Opened date"
                        @update="applyDateRange"
                    />
                    <input v-model="search" type="search" placeholder="Search register…" class="admin-data-table__search" />
                    <Link
                        :href="route('pos.registers.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-brand-navy hover:bg-gray-50"
                    >
                        Manage registers
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Register</th>
                            <th>Opened by</th>
                            <th>Opening cash</th>
                            <th>Sales</th>
                            <th>Expected</th>
                            <th>Orders</th>
                            <th>Opened</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="session in sessions.data" :key="session.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <p class="font-medium text-brand-navy">{{ session.register_name }}</p>
                                <p class="text-xs text-gray-400">{{ session.register_code }}</p>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ session.opened_by || '—' }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ money(session.opening_cash) }}</td>
                            <td class="admin-data-table__cell tabular-nums font-medium text-emerald-700">{{ money(session.sales_total) }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ money(session.expected_cash) }}</td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ session.orders_count }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ formatDateTime(session.opened_at) }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('pos.cash-management', { pos_session_id: session.id })"
                                        class="admin-data-table__action"
                                        title="Cash movements"
                                    >
                                        <ActionIcon name="cash" />
                                    </Link>
                                    <Link
                                        :href="route('pos.terminal')"
                                        class="admin-data-table__action"
                                        title="Open terminal"
                                    >
                                        <ActionIcon name="terminal" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!sessions.data.length">
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">
                                {{ search || dateFrom || dateTo ? 'No open sessions match these filters.' : 'No open sessions right now.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visit()">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="sessions" :links="sessions.links" />
            </div>
        </div>
    </AdminLayout>
</template>
