<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    payments: { type: Object, required: true },
    orders: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const meta = computed(() => paginationMeta(props.payments));

const form = useForm({
    purchase_order_id: '',
    amount: '',
    method: 'cash',
    reference: '',
    paid_at: new Date().toISOString().slice(0, 10),
    notes: '',
});

const deleteForm = useForm({});

const selectedOrder = computed(() => props.orders.find((o) => o.id === Number(form.purchase_order_id)));

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.method = 'cash';
    form.paid_at = new Date().toISOString().slice(0, 10);
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (payment) => {
    editing.value = payment;
    form.purchase_order_id = payment.purchase_order_id;
    form.amount = payment.amount;
    form.method = payment.method;
    form.reference = payment.reference || '';
    form.paid_at = payment.paid_at;
    form.notes = payment.notes || '';
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    };

    if (editing.value) {
        form.put(route('purchase.payments.update', editing.value.id), options);
    } else {
        form.post(route('purchase.payments.store'), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }
    deleteForm.delete(route('purchase.payments.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const visitIndex = () => {
    router.get(
        route('purchase.payments'),
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
    <Head title="Supplier Payments" />

    <AdminLayout title="Supplier Payments">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Supplier payments</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} recorded</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Record payment
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Payment #</th>
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Paid</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in payments.data" :key="payment.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ payment.number }}</td>
                            <td class="admin-data-table__cell">
                                <Link
                                    v-if="payment.purchase_order_id"
                                    :href="route('purchase.orders.show', payment.purchase_order_id)"
                                    class="text-brand-navy hover:text-brand-orange"
                                >
                                    {{ payment.order_number }}
                                </Link>
                                <span v-else>{{ payment.order_number }}</span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ payment.supplier_name }}</td>
                            <td class="admin-data-table__cell tabular-nums font-medium">
                                {{ payment.currency }} {{ Number(payment.amount).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell capitalize text-gray-600">{{ payment.method }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ payment.paid_at }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(payment)"
                                    >
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = payment"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!payments.data.length">
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No payments match your search.' : 'No payments yet.' }}
                                </p>
                                <button
                                    v-if="!search"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Record first payment
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
                <TablePagination :paginator="payments" :links="payments.links" />
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-brand-navy">
                    {{ editing ? 'Edit payment' : 'Record payment' }}
                </h3>
                <div v-if="!editing">
                    <InputLabel value="Purchase order" />
                    <select v-model="form.purchase_order_id" class="admin-filter-select mt-1 block w-full" required>
                        <option value="">Select PO…</option>
                        <option v-for="order in orders" :key="order.id" :value="order.id">
                            {{ order.number }} — bal {{ order.balance }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.purchase_order_id" />
                    <p v-if="selectedOrder" class="mt-1 text-xs text-gray-500">
                        Outstanding: {{ selectedOrder.currency }} {{ Number(selectedOrder.balance).toFixed(2) }}
                    </p>
                </div>
                <div v-if="!editing">
                    <InputLabel value="Amount" />
                    <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                    <InputError class="mt-1" :message="form.errors.amount" />
                </div>
                <div>
                    <InputLabel value="Method" />
                    <select v-model="form.method" class="admin-filter-select mt-1 block w-full">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="cheque">Cheque</option>
                        <option value="mobile">Mobile</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Reference" />
                    <TextInput v-model="form.reference" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Paid at" />
                    <TextInput v-model="form.paid_at" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-1" :message="form.errors.paid_at" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete payment?"
            :item-name="deleteTarget?.number"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
