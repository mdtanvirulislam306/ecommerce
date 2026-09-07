<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const actionForm = useForm({});
const showCancelModal = ref(false);

const deliveryForm = useForm({
    delivery_status: '',
    note: '',
});

const paymentForm = useForm({
    amount: '',
    method: 'cash',
    note: '',
});

const lineDeliveryOptions = computed(() =>
    (props.order.line_delivery_options || []).map((opt) => ({ id: opt.value, name: opt.label })),
);

const bulkDeliveryOptions = computed(() =>
    (props.order.delivery_next || []).map((opt) => ({ id: opt.value, name: opt.label })),
);

const paymentMethodOptions = computed(() =>
    (props.order.payment_methods || []).map((m) => ({ id: m.value, name: m.label })),
);

const lineForms = ref(
    Object.fromEntries(
        (props.order.items || []).map((item) => [
            item.id,
            {
                delivery_status: item.delivery_status,
                quantity_delivered: item.quantity_delivered,
            },
        ]),
    ),
);

const lineAction = useForm({
    delivery_status: '',
    quantity_delivered: '',
    note: '',
});

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    pending: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    cancelled: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const deliveryMeta = {
    pending: { class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    processing: { class: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200' },
    partial: { class: 'bg-orange-50 text-brand-orange ring-1 ring-orange-200' },
    shipped: { class: 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' },
    delivered: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    cancelled: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const paymentMeta = {
    unpaid: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    partial: { class: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200' },
    paid: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    refunded: { class: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200' },
};

const confirmOrder = () => {
    actionForm.post(route('sales.orders.confirm', props.order.id), { preserveScroll: true });
};

const confirmCancel = () => {
    actionForm.post(route('sales.orders.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
        },
    });
};

const submitDelivery = () => {
    deliveryForm.post(route('sales.orders.delivery-status', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            deliveryForm.reset();
            deliveryForm.delivery_status = '';
        },
    });
};

const submitPayment = () => {
    paymentForm.post(route('sales.orders.payments', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset();
            paymentForm.method = 'cash';
        },
    });
};

const submitLineDelivery = (item) => {
    const form = lineForms.value[item.id];
    lineAction.delivery_status = form.delivery_status;
    lineAction.quantity_delivered = form.quantity_delivered;
    lineAction.note = '';
    lineAction.post(route('sales.orders.line-delivery', [props.order.id, item.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="order.number" />

    <AdminLayout :title="order.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ flash.error }}
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('sales.orders.all')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                    ← Orders
                </Link>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusMeta[order.status]?.class">
                    {{ order.status_label }}
                </span>
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="deliveryMeta[order.delivery_status]?.class"
                >
                    Delivery: {{ order.delivery_status_label }}
                </span>
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="paymentMeta[order.payment_status]?.class"
                >
                    Payment: {{ order.payment_status_label }}
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    v-if="order.invoice_id"
                    :href="route('sales.invoices.print', order.invoice_id)"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-brand-navy hover:border-brand-teal/40"
                >
                    Print invoice
                </a>
                <Link
                    v-else-if="order.status === 'confirmed'"
                    :href="route('sales.invoices.create')"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-brand-navy hover:border-brand-teal/40"
                >
                    Create invoice
                </Link>
                <PrimaryButton v-if="order.can_confirm" type="button" :disabled="actionForm.processing" @click="confirmOrder">
                    Confirm & fulfill stock
                </PrimaryButton>
                <SecondaryButton
                    v-if="order.can_cancel"
                    type="button"
                    :disabled="actionForm.processing"
                    @click="showCancelModal = true"
                >
                    Cancel order
                </SecondaryButton>
            </div>
        </div>

        <div class="mb-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <section class="admin-card space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Customer</p>
                    <p class="font-medium text-brand-navy">{{ order.customer_name }}</p>
                    <p v-if="order.customer_email" class="text-gray-500">{{ order.customer_email }}</p>
                    <p v-if="order.customer_phone" class="text-gray-500">{{ order.customer_phone }}</p>
                    <Link
                        v-if="order.customer_id"
                        :href="route('crm.customers.show', order.customer_id)"
                        class="mt-1 inline-block text-xs font-medium text-brand-orange hover:underline"
                    >
                        Open in CRM
                    </Link>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created</p>
                    <p>{{ formatDateTime(order.created_at) }}</p>
                </div>
                <div v-if="order.invoice_number">
                    <p class="text-xs text-gray-500">Invoice</p>
                    <Link
                        :href="route('sales.invoices.show', order.invoice_id)"
                        class="font-medium text-brand-orange hover:underline"
                    >
                        {{ order.invoice_number }}
                    </Link>
                </div>
                <div class="border-t border-gray-100 pt-3 space-y-1">
                    <p class="text-xs text-gray-500">Totals</p>
                    <p class="text-xl font-semibold text-brand-navy">
                        {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                    </p>
                    <p class="text-xs text-gray-500">Paid {{ Number(order.amount_paid).toFixed(2) }}</p>
                    <p class="text-xs text-gray-500">Due {{ Number(order.amount_due).toFixed(2) }}</p>
                </div>
            </section>

            <div class="space-y-6">
                <section class="admin-card !p-0 overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-brand-navy">Line items & delivery</h2>
                        <p class="text-xs text-gray-400">Set delivery status per product for partial deliveries</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b border-gray-200 bg-gray-50/90">
                                <tr class="admin-data-table__head">
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Delivered</th>
                                    <th>Line status</th>
                                    <th>Unit</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.items" :key="item.id" class="admin-data-table__row">
                                    <td class="admin-data-table__cell">
                                        <p class="font-medium text-brand-navy">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500">{{ item.sku || '—' }}</p>
                                    </td>
                                    <td class="admin-data-table__cell tabular-nums">{{ Number(item.quantity).toFixed(2) }}</td>
                                    <td class="admin-data-table__cell">
                                        <TextInput
                                            v-if="order.can_update_delivery || order.delivery_status === 'partial'"
                                            v-model="lineForms[item.id].quantity_delivered"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-24"
                                            :disabled="order.status === 'cancelled'"
                                        />
                                        <span v-else class="tabular-nums">{{ Number(item.quantity_delivered).toFixed(2) }}</span>
                                    </td>
                                    <td class="admin-data-table__cell">
                                        <SearchableSelect
                                            v-if="order.status !== 'cancelled'"
                                            v-model="lineForms[item.id].delivery_status"
                                            :options="lineDeliveryOptions"
                                            placeholder="Status"
                                            :allow-clear="false"
                                        />
                                        <span
                                            v-else
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="deliveryMeta[item.delivery_status]?.class"
                                        >
                                            {{ item.delivery_status_label }}
                                        </span>
                                    </td>
                                    <td class="admin-data-table__cell tabular-nums">
                                        {{ Number(item.unit_price).toFixed(2) }}
                                    </td>
                                    <td class="admin-data-table__cell text-right tabular-nums font-medium">
                                        {{ Number(item.line_total).toFixed(2) }}
                                    </td>
                                    <td class="admin-data-table__cell text-right">
                                        <button
                                            v-if="order.status !== 'cancelled'"
                                            type="button"
                                            class="text-xs font-medium text-brand-orange hover:underline"
                                            :disabled="lineAction.processing"
                                            @click="submitLineDelivery(item)"
                                        >
                                            Save
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="grid gap-6 sm:grid-cols-2">
                    <section class="admin-card space-y-3">
                        <h2 class="text-sm font-semibold text-brand-navy">Bulk delivery</h2>
                        <p class="text-sm text-gray-600">
                            Order:
                            <span
                                class="ml-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="deliveryMeta[order.delivery_status]?.class"
                            >
                                {{ order.delivery_status_label }}
                            </span>
                        </p>
                        <form
                            v-if="order.can_update_delivery && order.delivery_next?.length"
                            class="space-y-3"
                            @submit.prevent="submitDelivery"
                        >
                            <div>
                                <InputLabel value="Set all lines to" />
                                <div class="mt-1">
                                    <SearchableSelect
                                        v-model="deliveryForm.delivery_status"
                                        :options="bulkDeliveryOptions"
                                        placeholder="Select…"
                                        :allow-clear="false"
                                    />
                                </div>
                            </div>
                            <TextInput v-model="deliveryForm.note" placeholder="Note (optional)" class="block w-full" />
                            <PrimaryButton type="submit" :disabled="deliveryForm.processing">Update all lines</PrimaryButton>
                        </form>
                        <p v-else class="text-xs text-gray-400">Use per-line controls for partial delivery.</p>
                    </section>

                    <section class="admin-card space-y-3">
                        <h2 class="text-sm font-semibold text-brand-navy">Record payment</h2>
                        <p class="text-sm text-gray-600">
                            Status:
                            <span
                                class="ml-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="paymentMeta[order.payment_status]?.class"
                            >
                                {{ order.payment_status_label }}
                            </span>
                        </p>
                        <p class="text-xs text-gray-400">
                            Due {{ order.currency }} {{ Number(order.amount_due).toFixed(2) }}. Enter amount for partial or full payment.
                        </p>
                        <form v-if="order.can_record_payment" class="space-y-3" @submit.prevent="submitPayment">
                            <div>
                                <InputLabel value="Amount paid" />
                                <TextInput
                                    v-model="paymentForm.amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="paymentForm.errors.amount" />
                            </div>
                            <div>
                                <InputLabel value="Method" />
                                <div class="mt-1">
                                    <SearchableSelect
                                        v-model="paymentForm.method"
                                        :options="paymentMethodOptions"
                                        placeholder="Method"
                                        :allow-clear="false"
                                    />
                                </div>
                            </div>
                            <TextInput v-model="paymentForm.note" placeholder="Note (optional)" class="block w-full" />
                            <PrimaryButton type="submit" :disabled="paymentForm.processing">Record payment</PrimaryButton>
                        </form>
                        <p v-else class="text-xs text-gray-400">No outstanding balance to collect.</p>
                    </section>
                </div>
            </div>
        </div>

        <section class="admin-card !p-0 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-navy">Activity log</h2>
                <p class="text-xs text-gray-400">
                    Order, delivery, payments, invoice, returns, and credit notes related to this order
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>When</th>
                            <th>Type</th>
                            <th>Change</th>
                            <th>Note</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in order.status_logs" :key="log.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                {{ formatDateTime(log.created_at) }}
                            </td>
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ log.field_label }}</td>
                            <td class="admin-data-table__cell text-sm">
                                <span class="text-gray-400">{{ log.from_label || '—' }}</span>
                                <span class="mx-1 text-gray-300">→</span>
                                <span class="font-medium text-brand-navy">{{ log.to_label }}</span>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ log.note || '—' }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ log.changed_by || '—' }}</td>
                        </tr>
                        <tr v-if="!order.status_logs?.length">
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">No activity logged yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <DeleteConfirmModal
            :show="showCancelModal"
            title="Cancel this sales order?"
            message="Confirmed orders will restock inventory. This action cannot be undone."
            :item-name="order.number"
            confirm-label="Cancel order"
            :processing="actionForm.processing"
            @close="showCancelModal = false"
            @confirm="confirmCancel"
        />
    </AdminLayout>
</template>
