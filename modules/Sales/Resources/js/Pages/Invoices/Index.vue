<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    invoices: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.invoices));

const statusMeta = {
    due: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    partial: { class: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200' },
    paid: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    overdue: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const visitIndex = () => {
    router.get(
        route('sales.invoices.all'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const setStatus = (value) => {
    status.value = value;
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch(status, visitIndex);
</script>

<template>
    <Head title="Invoices" />

    <AdminLayout title="Invoices">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Invoices</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search invoices…"
                        class="admin-data-table__search"
                    />
                    <Link
                        :href="route('sales.invoices.create')"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                    >
                        New invoice
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
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Due date</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in invoices.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('sales.invoices.show', row.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.customer_name }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusMeta[row.status]?.class"
                                >
                                    {{ row.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell tabular-nums font-medium">
                                {{ row.currency }} {{ Number(row.grand_total).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell tabular-nums">{{ Number(row.amount_paid).toFixed(2) }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ Number(row.amount_due).toFixed(2) }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ row.due_date || '—' }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ formatDateTime(row.created_at) }}
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link
                                        :href="route('sales.invoices.show', row.id)"
                                        class="admin-data-table__action"
                                        title="View"
                                    >
                                        <ActionIcon name="view" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data.length">
                            <td colspan="9" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{
                                        search || status ? 'No invoices match these filters.' : 'No invoices yet.'
                                    }}
                                </p>
                                <Link
                                    v-if="!search && !status"
                                    :href="route('sales.invoices.create')"
                                    class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                >
                                    Create first invoice
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
                <TablePagination :paginator="invoices" :links="invoices.links" />
            </div>
        </div>
    </AdminLayout>
</template>
