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
    completed: { class: 'bg-emerald-50 text-emerald-700' },
    cancelled: { class: 'bg-red-50 text-red-700' },
};

const cancelOrder = () => {
    if (!confirm('Cancel this POS sale? Stock will be restocked and cash journal reversed.')) return;
    actionForm.post(route('pos.orders.cancel', props.order.id), { preserveScroll: true });
};

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Head :title="order.number" />

    <AdminLayout :title="order.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy print:hidden">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <Link :href="route('pos.orders.index')" class="text-sm text-brand-navy hover:text-brand-orange">← POS orders</Link>
            <div class="flex gap-2">
                <PrimaryButton type="button" @click="printReceipt">Print receipt</PrimaryButton>
                <SecondaryButton v-if="order.can_cancel" type="button" :disabled="actionForm.processing" @click="cancelOrder">
                    Cancel sale
                </SecondaryButton>
            </div>
        </div>

        <section class="receipt-thermal mx-auto hidden max-w-[280px] bg-white p-3 font-mono text-[11px] text-black print:block">
            <p class="text-center text-sm font-bold">NexCore POS</p>
            <p class="text-center">{{ order.register_name || 'Register' }}</p>
            <p class="mt-2 text-center">{{ order.number }}</p>
            <p class="text-center">{{ formatDateTime(order.completed_at || order.created_at) }}</p>
            <hr class="my-2 border-dashed border-black" />
            <p>Customer: {{ order.customer_name || 'Walk-in' }}</p>
            <hr class="my-2 border-dashed border-black" />
            <div v-for="item in order.items" :key="item.id" class="mb-1">
                <p class="font-semibold">{{ item.name }}</p>
                <p class="flex justify-between">
                    <span>{{ Number(item.quantity).toFixed(2) }} × {{ Number(item.unit_price).toFixed(2) }}</span>
                    <span>{{ Number(item.line_total).toFixed(2) }}</span>
                </p>
            </div>
            <hr class="my-2 border-dashed border-black" />
            <p class="flex justify-between text-sm font-bold">
                <span>TOTAL</span>
                <span>{{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}</span>
            </p>
            <p class="flex justify-between">
                <span>Tendered</span>
                <span>{{ Number(order.amount_tendered || 0).toFixed(2) }}</span>
            </p>
            <p class="flex justify-between">
                <span>Change</span>
                <span>{{ Number(order.change_due || 0).toFixed(2) }}</span>
            </p>
            <p class="mt-3 text-center">Thank you</p>
        </section>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr] print:hidden">
            <section class="admin-card space-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusMeta[order.status]?.class">
                        {{ order.status_label }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Customer</p>
                    <p class="font-medium text-brand-navy">{{ order.customer_name || 'Walk-in' }}</p>
                    <Link
                        v-if="order.customer_id"
                        :href="route('crm.customers.show', order.customer_id)"
                        class="mt-1 inline-block text-xs font-medium text-brand-orange hover:underline print:hidden"
                    >
                        Open in CRM
                    </Link>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Register</p>
                    <p>{{ order.register_name || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Payment</p>
                    <p class="capitalize">{{ order.payment_method }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Tendered / Change</p>
                    <p>
                        {{ order.currency }} {{ Number(order.amount_tendered || 0).toFixed(2) }} /
                        {{ Number(order.change_due || 0).toFixed(2) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Completed</p>
                    <p>{{ formatDateTime(order.completed_at || order.created_at) }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500">Grand total</p>
                    <p class="text-xl font-semibold text-brand-navy">
                        {{ order.currency }} {{ Number(order.grand_total).toFixed(2) }}
                    </p>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Line items</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs text-gray-500">
                            <tr>
                                <th class="pb-2">Product</th>
                                <th class="pb-2">SKU</th>
                                <th class="pb-2">Qty</th>
                                <th class="pb-2">Unit</th>
                                <th class="pb-2 text-right">Total</th>
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

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .receipt-thermal,
    .receipt-thermal * {
        visibility: visible;
    }
    .receipt-thermal {
        position: absolute;
        left: 0;
        top: 0;
        width: 280px;
    }
}
</style>
