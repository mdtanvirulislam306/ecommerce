<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const actionForm = useForm({});
const showCancelModal = ref(false);

const receiveQtys = reactive(
    Object.fromEntries((props.order.items || []).map((item) => [item.id, Number(item.quantity_remaining) || 0])),
);

const receiveForm = useForm({
    items: [],
});

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    pending: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    approved: { class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' },
    partial: { class: 'bg-orange-50 text-brand-orange ring-1 ring-orange-200' },
    received: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    cancelled: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const approveOrder = () => {
    actionForm.post(route('purchase.orders.approve', props.order.id), { preserveScroll: true });
};

const confirmCancel = () => {
    actionForm.post(route('purchase.orders.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
        },
    });
};

const submitReceive = () => {
    receiveForm.items = props.order.items.map((item) => ({
        id: item.id,
        quantity: Number(receiveQtys[item.id] || 0),
    }));
    receiveForm.post(route('purchase.orders.receive', props.order.id), { preserveScroll: true });
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
        <div v-if="receiveForm.errors.items" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ receiveForm.errors.items }}
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('purchase.orders.all')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                    ← Orders
                </Link>
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusMeta[order.status]?.class"
                >
                    {{ order.status_label }}
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <PrimaryButton v-if="order.can_approve" type="button" :disabled="actionForm.processing" @click="approveOrder">
                    Approve PO
                </PrimaryButton>
                <SecondaryButton
                    v-if="order.can_cancel"
                    type="button"
                    :disabled="actionForm.processing"
                    @click="showCancelModal = true"
                >
                    Cancel
                </SecondaryButton>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <section class="admin-card space-y-4 text-sm">
                <div v-if="order.supplier">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Supplier</p>
                    <p class="mt-1 font-medium text-brand-navy">{{ order.supplier.name }}</p>
                    <p class="text-gray-500">{{ order.supplier.code }}</p>
                    <p v-if="order.supplier.email" class="text-gray-500">{{ order.supplier.email }}</p>
                    <p v-if="order.supplier.phone" class="text-gray-500">{{ order.supplier.phone }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Created</p>
                    <p class="mt-1">{{ formatDateTime(order.created_at) }}</p>
                </div>
                <div v-if="order.approved_at">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Approved</p>
                    <p class="mt-1">{{ formatDateTime(order.approved_at) }}</p>
                </div>
                <div v-if="order.received_at">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Fully received</p>
                    <p class="mt-1">{{ formatDateTime(order.received_at) }}</p>
                </div>
                <div v-if="order.notes">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Notes</p>
                    <p class="mt-1">{{ order.notes }}</p>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Grand total</p>
                    <p class="mt-1 text-xl font-semibold text-brand-navy">
                        {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                    </p>
                </div>
                <Link
                    v-if="order.can_receive"
                    :href="route('purchase.receive')"
                    class="inline-block text-xs font-medium text-brand-orange hover:underline"
                >
                    ← Receivable queue
                </Link>
            </section>

            <section class="admin-card !p-0 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-brand-navy">Line items</h2>
                        <p v-if="order.can_receive" class="text-xs text-gray-400">
                            Set qty to receive into inventory
                        </p>
                    </div>
                    <PrimaryButton
                        v-if="order.can_receive"
                        type="button"
                        :disabled="receiveForm.processing"
                        @click="submitReceive"
                    >
                        {{ receiveForm.processing ? 'Receiving…' : 'Receive into stock' }}
                    </PrimaryButton>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="border-b border-gray-200 bg-gray-50/90">
                            <tr class="admin-data-table__head">
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Ordered</th>
                                <th>Received</th>
                                <th>Remaining</th>
                                <th v-if="order.can_receive">Receive now</th>
                                <th>Unit cost</th>
                                <th class="text-right">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="admin-data-table__row">
                                <td class="admin-data-table__cell font-medium text-brand-navy">{{ item.name }}</td>
                                <td class="admin-data-table__cell font-mono text-xs text-gray-500">
                                    {{ item.sku || '—' }}
                                </td>
                                <td class="admin-data-table__cell tabular-nums">
                                    {{ Number(item.quantity_ordered).toFixed(2) }}
                                </td>
                                <td class="admin-data-table__cell tabular-nums">
                                    {{ Number(item.quantity_received).toFixed(2) }}
                                </td>
                                <td class="admin-data-table__cell tabular-nums">
                                    {{ Number(item.quantity_remaining).toFixed(2) }}
                                </td>
                                <td v-if="order.can_receive" class="admin-data-table__cell">
                                    <TextInput
                                        v-model="receiveQtys[item.id]"
                                        type="number"
                                        min="0"
                                        :max="Number(item.quantity_remaining)"
                                        step="any"
                                        class="w-24"
                                        :disabled="Number(item.quantity_remaining) <= 0"
                                    />
                                </td>
                                <td class="admin-data-table__cell tabular-nums">
                                    {{ item.currency }} {{ Number(item.unit_cost).toFixed(2) }}
                                </td>
                                <td class="admin-data-table__cell text-right tabular-nums font-medium">
                                    {{ item.currency }} {{ Number(item.line_total).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="order.can_receive" class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
                    Receiving posts Inventory purchase_receive movements for this PO.
                </p>
            </section>
        </div>

        <DeleteConfirmModal
            :show="showCancelModal"
            title="Cancel this purchase order?"
            message="Cancelled POs cannot be approved or received. This action cannot be undone."
            :item-name="order.number"
            confirm-label="Cancel PO"
            :processing="actionForm.processing"
            @close="showCancelModal = false"
            @confirm="confirmCancel"
        />
    </AdminLayout>
</template>
