<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);

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
                <h2 class="text-sm font-semibold text-brand-navy">Receivable purchase orders</h2>
                <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Lines</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id">
                            <td class="font-medium text-brand-navy">{{ order.number }}</td>
                            <td>{{ order.supplier_name }}</td>
                            <td class="capitalize">{{ order.status_label }}</td>
                            <td>{{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}</td>
                            <td>{{ order.items_count }}</td>
                            <td class="text-right">
                                <Link :href="route('purchase.orders.show', order.id)" class="text-sm text-brand-orange hover:underline">
                                    Receive
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!orders.data.length">
                            <td colspan="6" class="py-10 text-center text-gray-500">No receivable orders.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :paginator="orders"
                :per-page="perPage"
                :per-page-options="perPageOptions"
                @change-page="(p) => router.get(route('purchase.receive'), { search: search || undefined, per_page: perPage, page: p }, { preserveState: true, replace: true })"
                @change-per-page="(v) => { perPage = v; visitIndex(); }"
            />
        </div>
    </AdminLayout>
</template>
