<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminDateRangePicker from '@/Components/Admin/AdminDateRangePicker.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router } from '@inertiajs/vue3';
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

const variance = (session) => {
    if (session.closing_cash == null || session.expected_cash == null) {
        return null;
    }
    return Number(session.closing_cash) - Number(session.expected_cash);
};

const visit = (page = 1) => {
    router.get(
        route('pos.session-history'),
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
    <Head title="Session History" />

    <AdminLayout title="Session History">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Closed sessions</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} closed</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminDateRangePicker
                        :from="dateFrom"
                        :to="dateTo"
                        placeholder="Closed date"
                        @update="applyDateRange"
                    />
                    <input v-model="search" type="search" placeholder="Search register…" class="admin-data-table__search" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Register</th>
                            <th>Opening</th>
                            <th>Closing</th>
                            <th>Expected</th>
                            <th>Variance</th>
                            <th>Sales</th>
                            <th>Orders</th>
                            <th>Closed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="session in sessions.data" :key="session.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <p class="font-medium text-brand-navy">{{ session.register_name }}</p>
                                <p class="text-xs text-gray-400">{{ session.register_code }}</p>
                            </td>
                            <td class="admin-data-table__cell tabular-nums">{{ money(session.opening_cash) }}</td>
                            <td class="admin-data-table__cell tabular-nums">
                                {{ session.closing_cash != null ? money(session.closing_cash) : '—' }}
                            </td>
                            <td class="admin-data-table__cell tabular-nums">
                                {{ session.expected_cash != null ? money(session.expected_cash) : '—' }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    v-if="variance(session) !== null"
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                                    :class="
                                        Math.abs(variance(session)) < 0.01
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : variance(session) > 0
                                              ? 'bg-sky-50 text-sky-800 ring-sky-200'
                                              : 'bg-amber-50 text-amber-800 ring-amber-200'
                                    "
                                >
                                    {{ variance(session) > 0 ? '+' : '' }}{{ money(variance(session)) }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="admin-data-table__cell tabular-nums font-medium">{{ money(session.sales_total) }}</td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ session.orders_count }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ formatDateTime(session.closed_at) }}</td>
                        </tr>
                        <tr v-if="!sessions.data.length">
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">
                                {{ search || dateFrom || dateTo ? 'No sessions match these filters.' : 'No closed sessions yet.' }}
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
