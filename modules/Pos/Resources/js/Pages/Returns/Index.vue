<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminDateRangePicker from '@/Components/Admin/AdminDateRangePicker.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    returns: { type: Object, required: true },
    orders: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const meta = computed(() => paginationMeta(props.returns));
const form = useForm({ pos_order_id: '', reason: '', notes: '', items: [] });
const selectedOrder = computed(() => props.orders.find((o) => o.id === Number(form.pos_order_id)));

const money = (currency, value) =>
    `${currency || 'BDT'} ${Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

watch(() => form.pos_order_id, (id) => {
    const order = props.orders.find((o) => o.id === Number(id));
    form.items = (order?.items || []).map((item) => ({
        pos_order_item_id: item.id,
        quantity: '',
        label: item.name,
        max: Math.max(0, Number(item.quantity) - Number(item.already_returned)),
    }));
});

const visit = (pageNum = 1) => {
    router.get(
        route('pos.returns'),
        {
            search: search.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: perPage.value,
            page: pageNum > 1 ? pageNum : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const applyDateRange = ({ from, to }) => {
    dateFrom.value = from || '';
    dateTo.value = to || '';
    visit();
};

const save = () => {
    form.transform((data) => ({
        ...data,
        items: data.items.filter((i) => Number(i.quantity) > 0).map((i) => ({
            pos_order_item_id: i.pos_order_item_id,
            quantity: i.quantity,
        })),
    })).post(route('pos.returns.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

let t = null;
watch(search, () => {
    clearTimeout(t);
    t = setTimeout(() => visit(), 300);
});
</script>

<template>
    <Head title="POS Returns" />

    <AdminLayout title="POS Returns">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">POS returns</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} returns</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminDateRangePicker
                        :from="dateFrom"
                        :to="dateTo"
                        placeholder="Returned date"
                        @update="applyDateRange"
                    />
                    <input v-model="search" type="search" placeholder="Search return or order…" class="admin-data-table__search" />
                    <PrimaryButton type="button" @click="showModal = true">New return</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Return #</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Reason</th>
                            <th>Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in returns.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.number }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.order_number }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.customer_name || '—' }}</td>
                            <td class="admin-data-table__cell tabular-nums text-gray-600">{{ row.items_count }}</td>
                            <td class="admin-data-table__cell tabular-nums font-medium">
                                {{ money(row.currency, row.grand_total) }}
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.reason || '—' }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ formatDateTime(row.returned_at) }}</td>
                        </tr>
                        <tr v-if="!returns.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">
                                {{ search || dateFrom || dateTo ? 'No returns match these filters.' : 'No POS returns yet.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visit()">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="returns" :links="returns.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-brand-navy">New POS return</h3>
                <div>
                    <InputLabel value="POS order" />
                    <select v-model="form.pos_order_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select…</option>
                        <option v-for="order in orders" :key="order.id" :value="order.id">
                            {{ order.number }} — {{ order.customer_name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.pos_order_id" />
                </div>
                <div v-if="selectedOrder" class="space-y-2 rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.pos_order_item_id"
                        class="grid grid-cols-[1fr_120px] items-center gap-3 text-sm"
                    >
                        <div>
                            <div class="font-medium text-brand-navy">{{ item.label }}</div>
                            <div class="text-xs text-gray-500">Returnable {{ item.max }}</div>
                        </div>
                        <TextInput v-model="form.items[index].quantity" type="number" min="0" step="0.0001" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Reason" />
                    <TextInput v-model="form.reason" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save return</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
