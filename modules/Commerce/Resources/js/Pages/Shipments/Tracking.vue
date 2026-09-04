<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    shipments: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.shipments));

const statusClass = (status) => {
    const map = {
        pending: 'bg-gray-100 text-gray-600',
        shipped: 'bg-sky-50 text-sky-700',
        in_transit: 'bg-amber-50 text-amber-800',
        delivered: 'bg-emerald-50 text-emerald-700',
        cancelled: 'bg-red-50 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('commerce.shipping.tracking'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Shipment Tracking" />

    <AdminLayout title="Shipment Tracking">
        <p class="mb-4 text-sm text-gray-500">Search and monitor shipment status across couriers.</p>

        <div class="mb-4">
            <Link :href="route('commerce.shipping.shipments.index')" class="text-sm text-brand-orange hover:underline">
                Manage shipments
            </Link>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Tracked shipments</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <input v-model="search" type="search" placeholder="Search tracking # or recipient…" class="admin-data-table__search" />
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Tracking #</th>
                            <th>Courier</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Destination</th>
                            <th>Shipped</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in shipments.data" :key="item.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ item.tracking_number }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.courier?.name || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.recipient_name }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    :class="statusClass(item.status)"
                                >
                                    {{ item.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.destination || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.shipped_at || '—' }}</td>
                        </tr>
                        <tr v-if="shipments.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No shipments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="shipments" :links="shipments.links" />
            </div>
        </div>
    </AdminLayout>
</template>
