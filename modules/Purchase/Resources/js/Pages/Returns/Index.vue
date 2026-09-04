<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);

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
                <h2 class="text-sm font-semibold text-brand-navy">Purchase returns</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <PrimaryButton type="button" @click="openCreate">New return</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Return #</th>
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Reason</th>
                            <th>Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in returns.data" :key="row.id">
                            <td class="font-medium text-brand-navy">{{ row.number }}</td>
                            <td>{{ row.order_number }}</td>
                            <td>{{ row.supplier_name }}</td>
                            <td>{{ row.currency }} {{ Number(row.grand_total).toFixed(2) }}</td>
                            <td>{{ row.reason || '—' }}</td>
                            <td class="text-sm text-gray-500">{{ row.returned_at ? new Date(row.returned_at).toLocaleString() : '—' }}</td>
                        </tr>
                        <tr v-if="!returns.data.length">
                            <td colspan="6" class="py-10 text-center text-gray-500">No purchase returns yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :paginator="returns"
                :per-page="perPage"
                :per-page-options="perPageOptions"
                @change-page="(p) => router.get(route('purchase.returns'), { search: search || undefined, per_page: perPage, page: p }, { preserveState: true, replace: true })"
                @change-per-page="(v) => { perPage = v; visitIndex(); }"
            />
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">New purchase return</h3>
                <form class="mt-4 space-y-4" @submit.prevent="save">
                    <div>
                        <InputLabel value="Purchase order" />
                        <select v-model="form.purchase_order_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select PO…</option>
                            <option v-for="order in orders" :key="order.id" :value="order.id">
                                {{ order.number }} — {{ order.supplier_name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.purchase_order_id" />
                    </div>
                    <div v-if="selectedOrder" class="space-y-2 rounded-lg border border-gray-100 p-3">
                        <div v-for="(item, index) in form.items" :key="item.purchase_order_item_id" class="grid grid-cols-[1fr_120px] items-center gap-3 text-sm">
                            <div>
                                <div class="font-medium text-brand-navy">{{ item.label }}</div>
                                <div class="text-xs text-gray-500">Returnable: {{ item.max }}</div>
                            </div>
                            <TextInput v-model="form.items[index].quantity" type="number" min="0" step="0.0001" :max="item.max" class="w-full" />
                        </div>
                        <InputError :message="form.errors.items" />
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
            </div>
        </Modal>
    </AdminLayout>
</template>
