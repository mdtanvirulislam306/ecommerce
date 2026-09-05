<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const actionForm = useForm({});

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    pending: { class: 'bg-amber-50 text-amber-800' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const confirmOrder = () => {
    actionForm.post(route('sales.orders.confirm', props.order.id), { preserveScroll: true });
};

const cancelOrder = () => {
    if (!confirm('Cancel this order? Confirmed orders will restock inventory.')) return;
    actionForm.post(route('sales.orders.cancel', props.order.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="order.number" />

    <AdminLayout :title="order.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('sales.orders.all')" class="text-sm text-brand-navy hover:text-brand-orange">← Orders</Link>
            <div class="flex flex-wrap gap-2">
                <PrimaryButton v-if="order.can_confirm" type="button" :disabled="actionForm.processing" @click="confirmOrder">
                    Confirm & fulfill stock
                </PrimaryButton>
                <SecondaryButton v-if="order.can_cancel" type="button" :disabled="actionForm.processing" @click="cancelOrder">
                    Cancel order
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
                <div v-if="order.confirmed_at">
                    <p class="text-xs text-gray-500">Confirmed</p>
                    <p>{{ formatDateTime(order.confirmed_at) }}</p>
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
