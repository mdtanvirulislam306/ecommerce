<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
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
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const meta = computed(() => paginationMeta(props.returns));

const form = useForm({
    purchase_order_id: '',
    reason: '',
    notes: '',
    items: [],
});

const selectedOrder = computed(() => props.orders.find((o) => o.id === Number(form.purchase_order_id)));

watch(
    () => form.purchase_order_id,
    (id) => {
        const order = props.orders.find((o) => o.id === Number(id));
        form.items = (order?.items || []).map((item) => ({
            purchase_order_item_id: item.id,
            quantity: '',
            label: item.name,
            max: Math.max(0, Number(item.quantity_received) - Number(item.already_returned)),
        }));
    },
);

const openCreate = () => {
    form.reset();
    form.items = [];
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    form
        .transform((data) => ({
            ...data,
            items: data.items
                .filter((item) => Number(item.quantity) > 0)
                .map((item) => ({
                    purchase_order_item_id: item.purchase_order_item_id,
                    quantity: item.quantity,
                })),
        }))
        .post(route('purchase.returns.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
};

const visitIndex = () => {
    router.get(
        route('purchase.returns'),
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
    <Head title="Purchase Returns" />

    <AdminLayout title="Purchase Returns">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Purchase returns</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} returns</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        New return
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Return #</th>
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Reason</th>
                            <th>Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in returns.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.number }}</td>
                            <td class="admin-data-table__cell">
                                <Link
                                    v-if="row.purchase_order_id"
                                    :href="route('purchase.orders.show', row.purchase_order_id)"
                                    class="text-brand-navy hover:text-brand-orange"
                                >
                                    {{ row.order_number }}
                                </Link>
                                <span v-else>{{ row.order_number }}</span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.supplier_name }}</td>
                            <td class="admin-data-table__cell tabular-nums">
                                {{ row.currency }} {{ Number(row.grand_total).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.reason || '—' }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ row.returned_at ? formatDateTime(row.returned_at) : '—' }}
                            </td>
                        </tr>
                        <tr v-if="!returns.data.length">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No returns match your search.' : 'No purchase returns yet.' }}
                                </p>
                                <button
                                    v-if="!search"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Create first return
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="returns" :links="returns.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-brand-navy">New purchase return</h3>
                <div>
                    <InputLabel value="Purchase order" />
                    <select v-model="form.purchase_order_id" class="admin-filter-select mt-1 block w-full" required>
                        <option value="">Select PO…</option>
                        <option v-for="order in orders" :key="order.id" :value="order.id">
                            {{ order.number }} — {{ order.supplier_name }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.purchase_order_id" />
                </div>
                <div v-if="selectedOrder" class="space-y-2 rounded-xl border border-gray-100 bg-gray-50/50 p-3">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.purchase_order_item_id"
                        class="grid grid-cols-[1fr_120px] items-center gap-3 text-sm"
                    >
                        <div>
                            <div class="font-medium text-brand-navy">{{ item.label }}</div>
                            <div class="text-xs text-gray-500">Returnable: {{ item.max }}</div>
                        </div>
                        <TextInput
                            v-model="form.items[index].quantity"
                            type="number"
                            min="0"
                            step="0.0001"
                            :max="item.max"
                            class="w-full"
                        />
                    </div>
                    <InputError :message="form.errors.items" />
                </div>
                <div>
                    <InputLabel value="Reason" />
                    <TextInput v-model="form.reason" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save return</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
