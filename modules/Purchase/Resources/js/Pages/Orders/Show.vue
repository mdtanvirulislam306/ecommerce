<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const actionForm = useForm({});

const receiveQtys = reactive(
    Object.fromEntries((props.order.items || []).map((item) => [item.id, Number(item.quantity_remaining) || 0])),
);

const receiveForm = useForm({
    items: [],
});

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    pending: { class: 'bg-amber-50 text-amber-800' },
    approved: { class: 'bg-sky-50 text-sky-700' },
    partial: { class: 'bg-orange-50 text-brand-orange' },
    received: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const approveOrder = () => {
    actionForm.post(route('purchase.orders.approve', props.order.id), { preserveScroll: true });
};

const cancelOrder = () => {
    if (!confirm('Cancel this purchase order?')) return;
    actionForm.post(route('purchase.orders.cancel', props.order.id), { preserveScroll: true });
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
        <div v-if="receiveForm.errors.items" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ receiveForm.errors.items }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('purchase.orders.all')" class="text-sm text-brand-navy hover:text-brand-orange">← Orders</Link>
            <div class="flex flex-wrap gap-2">
                <PrimaryButton v-if="order.can_approve" type="button" :disabled="actionForm.processing" @click="approveOrder">
                    Approve PO
                </PrimaryButton>
                <SecondaryButton v-if="order.can_cancel" type="button" :disabled="actionForm.processing" @click="cancelOrder">
                    Cancel
                </SecondaryButton>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <section class="admin-card space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <span
                        class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="statusMeta[order.status]?.class"
                    >
                        {{ order.status_label }}
                    </span>
                </div>
                <div v-if="order.supplier">
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="font-medium text-brand-navy">{{ order.supplier.name }}</p>
                    <p class="text-gray-500">{{ order.supplier.code }}</p>
                    <p v-if="order.supplier.email" class="text-gray-500">{{ order.supplier.email }}</p>
                    <p v-if="order.supplier.phone" class="text-gray-500">{{ order.supplier.phone }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created</p>
                    <p>{{ formatDateTime(order.created_at) }}</p>
                </div>
                <div v-if="order.approved_at">
                    <p class="text-xs text-gray-500">Approved</p>
                    <p>{{ formatDateTime(order.approved_at) }}</p>
                </div>
                <div v-if="order.received_at">
                    <p class="text-xs text-gray-500">Fully received</p>
                    <p>{{ formatDateTime(order.received_at) }}</p>
                </div>
                <div v-if="order.notes">
                    <p class="text-xs text-gray-500">Notes</p>
                    <p>{{ order.notes }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500">Grand total</p>
                    <p class="text-xl font-semibold text-brand-navy">
                        {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                    </p>
                </div>
            </section>

            <section class="admin-card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-brand-navy">Line items</h2>
                    <PrimaryButton
                        v-if="order.can_receive"
                        type="button"
                        :disabled="receiveForm.processing"
                        @click="submitReceive"
                    >
                        Receive into stock
                    </PrimaryButton>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs text-gray-500">
                            <tr>
                                <th class="pb-2">Product</th>
                                <th class="pb-2">SKU</th>
                                <th class="pb-2">Ordered</th>
                                <th class="pb-2">Received</th>
                                <th class="pb-2">Remaining</th>
                                <th v-if="order.can_receive" class="pb-2">Receive now</th>
                                <th class="pb-2">Unit cost</th>
                                <th class="pb-2 text-right">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-t border-gray-50">
                                <td class="py-2 font-medium text-brand-navy">{{ item.name }}</td>
                                <td class="py-2 text-gray-500">{{ item.sku || '—' }}</td>
                                <td class="py-2">{{ Number(item.quantity_ordered).toFixed(2) }}</td>
                                <td class="py-2">{{ Number(item.quantity_received).toFixed(2) }}</td>
                                <td class="py-2">{{ Number(item.quantity_remaining).toFixed(2) }}</td>
                                <td v-if="order.can_receive" class="py-2">
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
                                <td class="py-2">{{ item.currency }} {{ Number(item.unit_cost).toFixed(2) }}</td>
                                <td class="py-2 text-right font-medium">
                                    {{ item.currency }} {{ Number(item.line_total).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="order.can_receive" class="mt-3 text-xs text-gray-500">
                    Receiving posts Inventory <code>purchase_receive</code> movements for this PO.
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
