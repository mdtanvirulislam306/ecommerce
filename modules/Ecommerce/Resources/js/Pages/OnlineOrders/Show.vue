<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const actionForm = useForm({});

const statusMeta = {
    pending: { class: 'bg-amber-50 text-amber-800' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const paymentStatusMeta = {
    unpaid: 'bg-gray-100 text-gray-700',
    pending: 'bg-amber-50 text-amber-800',
    paid: 'bg-emerald-50 text-emerald-700',
    failed: 'bg-red-50 text-red-700',
};

const confirmOrder = () => {
    actionForm.post(route('ecommerce.online-orders.confirm', props.order.id), { preserveScroll: true });
};

const linkCopied = ref(false);

const copyTrackingLink = async () => {
    try {
        await navigator.clipboard.writeText(props.order.tracking_url);
        linkCopied.value = true;
        setTimeout(() => (linkCopied.value = false), 2000);
    } catch {
        linkCopied.value = false;
    }
};

const cancelOrder = () => {
    if (!confirm('Cancel this online order?')) return;
    actionForm.post(route('ecommerce.online-orders.cancel', props.order.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="order.number" />

    <AdminLayout :title="order.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('ecommerce.online-orders.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Online orders
            </Link>
            <div class="flex flex-wrap gap-2">
                <PrimaryButton v-if="order.can_confirm" type="button" :disabled="actionForm.processing" @click="confirmOrder">
                    Confirm & fulfill stock
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
                    <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusMeta[order.status]?.class">
                        {{ order.status_label }}
                    </span>
                </div>
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
                    <p class="text-xs text-gray-500">Ship to</p>
                    <p class="whitespace-pre-line">{{ order.shipping_address }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Payment</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ order.payment_method_label }}</span>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="paymentStatusMeta[order.payment_status]">
                            {{ order.payment_status_label }}
                        </span>
                    </div>
                    <dl v-if="order.payment_status === 'paid'" class="mt-2 space-y-1 rounded-lg bg-gray-50 p-2.5 text-xs">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Paid</dt>
                            <dd class="text-right">{{ formatDateTime(order.paid_at) }}</dd>
                        </div>
                        <div v-if="order.payment_card_type" class="flex justify-between gap-2">
                            <dt class="text-gray-500">Via</dt>
                            <dd class="text-right">{{ order.payment_card_type }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Transaction</dt>
                            <dd class="break-all text-right font-mono">{{ order.payment_transaction_id }}</dd>
                        </div>
                        <div v-if="order.payment_bank_transaction_id" class="flex justify-between gap-2">
                            <dt class="text-gray-500">Bank ref</dt>
                            <dd class="break-all text-right font-mono">{{ order.payment_bank_transaction_id }}</dd>
                        </div>
                    </dl>
                    <p v-else-if="order.payment_status === 'pending' || order.payment_status === 'failed'" class="mt-2 rounded-lg bg-amber-50 p-2.5 text-xs text-amber-800">
                        The customer hasn't paid yet. Wait for payment before confirming.
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created</p>
                    <p>{{ formatDateTime(order.created_at) }}</p>
                </div>
                <div v-if="order.tracking_url">
                    <p class="text-xs text-gray-500">Customer tracking link</p>
                    <div class="mt-1 flex items-center gap-2">
                        <a :href="order.tracking_url" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate text-xs text-brand-teal-dark hover:underline">
                            {{ order.tracking_url }}
                        </a>
                        <button type="button" class="shrink-0 rounded-md border border-gray-200 px-2 py-1 text-xs text-brand-navy hover:bg-gray-50" @click="copyTrackingLink">
                            {{ linkCopied ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                </div>
                <dl class="space-y-1 border-t border-gray-100 pt-3">
                    <div class="flex justify-between text-gray-600">
                        <dt>Subtotal</dt>
                        <dd>{{ order.currency }} {{ Number(order.subtotal).toFixed(2) }}</dd>
                    </div>
                    <div v-if="Number(order.discount_total) > 0" class="flex justify-between text-emerald-700">
                        <dt>Coupon {{ order.coupon_code }}</dt>
                        <dd>−{{ Number(order.discount_total).toFixed(2) }}</dd>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <dt>Delivery<span v-if="order.delivery_zone"> ({{ order.delivery_zone }})</span></dt>
                        <dd>{{ Number(order.shipping_fee) === 0 ? 'Free' : Number(order.shipping_fee).toFixed(2) }}</dd>
                    </div>
                    <div class="pt-2">
                        <p class="text-xs text-gray-500">Grand total</p>
                        <p class="text-xl font-semibold text-brand-navy">
                            {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                        </p>
                    </div>
                </dl>
            </section>

            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Line items (snapshots)</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs text-gray-500">
                            <tr>
                                <th class="pb-2">Product</th>
                                <th class="pb-2">SKU</th>
                                <th class="pb-2">Qty</th>
                                <th class="pb-2">Unit price</th>
                                <th class="pb-2 text-right">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-t border-gray-50">
                                <td class="py-2 font-medium text-brand-navy">{{ item.name }}</td>
                                <td class="py-2 text-gray-500">{{ item.sku || '—' }}</td>
                                <td class="py-2">{{ Number(item.quantity).toFixed(2) }}</td>
                                <td class="py-2">{{ item.currency }} {{ Number(item.unit_price).toFixed(2) }}</td>
                                <td class="py-2 text-right font-medium">
                                    {{ item.currency }} {{ Number(item.line_total).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
