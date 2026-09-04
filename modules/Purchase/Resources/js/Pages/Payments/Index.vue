<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
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
    if (!deleteTarget.value) return;
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
                <h2 class="text-sm font-semibold text-brand-navy">Supplier payments</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <PrimaryButton type="button" @click="openCreate">Record payment</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>PO</th>
                            <th>Supplier</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Paid</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in payments.data" :key="payment.id">
                            <td class="font-medium text-brand-navy">{{ payment.number }}</td>
                            <td>{{ payment.order_number }}</td>
                            <td>{{ payment.supplier_name }}</td>
                            <td>{{ payment.currency }} {{ Number(payment.amount).toFixed(2) }}</td>
                            <td class="capitalize">{{ payment.method }}</td>
                            <td>{{ payment.paid_at }}</td>
                            <td class="space-x-2 text-right">
                                <button type="button" class="text-sm text-brand-navy hover:text-brand-orange" @click="openEdit(payment)">Edit</button>
                                <button type="button" class="text-sm text-red-600" @click="deleteTarget = payment">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!payments.data.length">
                            <td colspan="7" class="py-10 text-center text-gray-500">No payments yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination
                :paginator="payments"
                :per-page="perPage"
                :per-page-options="perPageOptions"
                @change-page="(p) => router.get(route('purchase.payments'), { search: search || undefined, per_page: perPage, page: p }, { preserveState: true, replace: true })"
                @change-per-page="(v) => { perPage = v; visitIndex(); }"
            />
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit payment' : 'Record payment' }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="save">
                    <div v-if="!editing">
                        <InputLabel value="Purchase order" />
                        <select v-model="form.purchase_order_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select PO…</option>
                            <option v-for="order in orders" :key="order.id" :value="order.id">
                                {{ order.number }} — bal {{ order.balance }}
                            </option>
                        </select>
                        <InputError :message="form.errors.purchase_order_id" />
                        <p v-if="selectedOrder" class="mt-1 text-xs text-gray-500">
                            Outstanding: {{ selectedOrder.currency }} {{ Number(selectedOrder.balance).toFixed(2) }}
                        </p>
                    </div>
                    <div v-if="!editing">
                        <InputLabel value="Amount" />
                        <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Method" />
                        <select v-model="form.method" class="mt-1 w-full rounded-md border-gray-300 text-sm">
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
                        <InputError :message="form.errors.paid_at" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal :show="!!deleteTarget" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="confirmDelete" />
    </AdminLayout>
</template>
