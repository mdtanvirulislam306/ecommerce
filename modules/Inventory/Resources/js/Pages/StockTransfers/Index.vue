<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    transfers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.transfers));

const visitIndex = () => {
    router.get(
        route('inventory.stock-transfer.index'),
        {
            search: search.value || undefined,
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
</script>

<template>
    <Head title="Stock Transfer" />

    <AdminLayout title="Stock Transfer">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Transfers</h2>
                    <p class="text-xs text-gray-500">
                        {{ meta.total }} total · moves stock between warehouses
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search transfers…"
                        class="admin-data-table__search"
                    />
                    <Link
                        :href="route('inventory.stock-transfer.create')"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                    >
                        New transfer
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Transfer</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Lines</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in transfers.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.number }}</td>
                            <td class="admin-data-table__cell">
                                <span class="text-brand-navy">{{ row.from_warehouse }}</span>
                                <span class="ml-1 text-xs text-gray-400">({{ row.from_warehouse_code }})</span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span class="text-brand-navy">{{ row.to_warehouse }}</span>
                                <span class="ml-1 text-xs text-gray-400">({{ row.to_warehouse_code }})</span>
                            </td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ row.items_count }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
                                >
                                    {{ row.status_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ formatDateTime(row.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!transfers.data.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No transfers match your search.' : 'No transfers yet.' }}
                                </p>
                                <Link
                                    v-if="!search"
                                    :href="route('inventory.stock-transfer.create')"
                                    class="mt-3 inline-block text-sm font-medium text-brand-orange hover:underline"
                                >
                                    Create first transfer
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
                <TablePagination :paginator="transfers" :links="transfers.links" />
            </div>
        </div>
    </AdminLayout>
</template>
