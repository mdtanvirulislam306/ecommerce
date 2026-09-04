<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    quotations: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    listTitle: { type: String, default: 'All Quotations' },
    statusOptions: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.quotations));

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    sent: { class: 'bg-sky-50 text-sky-800' },
    accepted: { class: 'bg-emerald-50 text-emerald-700' },
    expired: { class: 'bg-red-50 text-red-700' },
};

const visitIndex = () => {
    router.get(
        route('sales.quotations.all'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
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
watch(status, visitIndex);
</script>

<template>
    <Head :title="listTitle" />

    <AdminLayout :title="listTitle">
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">{{ listTitle }}</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search quotations…" class="admin-data-table__search" />
                    <select v-model="status" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All statuses</option>
                        <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <Link :href="route('sales.quotations.create')">
                        <PrimaryButton type="button">New quotation</PrimaryButton>
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Quotation</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Valid until</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in quotations.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('sales.quotations.show', row.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td class="admin-data-table__cell">{{ row.customer_name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.items_count }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusMeta[row.status]?.class"
                                >
                                    {{ row.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell font-medium">
                                {{ row.currency }} {{ Number(row.grand_total).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ row.valid_until || '—' }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ formatDateTime(row.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!quotations.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">No quotations found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="quotations" :links="quotations.links" />
            </div>
        </div>
    </AdminLayout>
</template>
