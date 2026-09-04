<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    payments: { type: Object, required: true },
    openInvoices: { type: Array, default: () => [] },
    methods: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.payments));

const form = useForm({
    sales_invoice_id: '',
    amount: '',
    method: 'cash',
    paid_at: '',
    notes: '',
});

const visitIndex = () => {
    router.get(
        route('sales.payments.index'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});

watch(
    () => form.sales_invoice_id,
    (id) => {
        const invoice = props.openInvoices.find((row) => String(row.id) === String(id));
        if (invoice) form.amount = invoice.amount_due;
    },
);

const submit = () => {
    form.post(route('sales.payments.store'), {
        onSuccess: () => form.reset('amount', 'notes', 'paid_at'),
    });
};
</script>

<template>
    <Head title="Payments" />

    <AdminLayout title="Payments">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="admin-card mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="submit">
            <div class="lg:col-span-2">
                <InputLabel value="Invoice" />
                <select v-model="form.sales_invoice_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                    <option value="">Select open invoice…</option>
                    <option v-for="inv in openInvoices" :key="inv.id" :value="inv.id">
                        {{ inv.number }} — {{ inv.customer_name }} (due {{ Number(inv.amount_due).toFixed(2) }})
                    </option>
                </select>
                <InputError :message="form.errors.sales_invoice_id" />
            </div>
            <div>
                <InputLabel value="Amount" />
                <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" required />
                <InputError :message="form.errors.amount" />
            </div>
            <div>
                <InputLabel value="Method" />
                <select v-model="form.method" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <PrimaryButton :disabled="form.processing">Record payment</PrimaryButton>
            </div>
        </form>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Payment history</h2>
                <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Payment</th>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Paid at</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in payments.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium">{{ row.number }}</td>
                            <td class="admin-data-table__cell">{{ row.invoice_number }}</td>
                            <td class="admin-data-table__cell">{{ row.customer_name }}</td>
                            <td class="admin-data-table__cell">{{ row.method_label }}</td>
                            <td class="admin-data-table__cell font-medium">
                                {{ row.currency }} {{ Number(row.amount).toFixed(2) }}
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ formatDateTime(row.paid_at || row.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!payments.data.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No payments yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="payments" :links="payments.links" />
            </div>
        </div>
    </AdminLayout>
</template>
