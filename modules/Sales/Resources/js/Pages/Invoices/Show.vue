<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    invoice: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const statusMeta = {
    due: { class: 'bg-amber-50 text-amber-800' },
    partial: { class: 'bg-sky-50 text-sky-800' },
    paid: { class: 'bg-emerald-50 text-emerald-700' },
    overdue: { class: 'bg-red-50 text-red-700' },
};
</script>

<template>
    <Head :title="invoice.number" />

    <AdminLayout :title="invoice.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('sales.invoices.all')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Invoices
            </Link>
            <div class="flex gap-3 text-sm">
                <Link
                    v-if="invoice.sales_order_id"
                    :href="route('sales.orders.show', invoice.sales_order_id)"
                    class="text-brand-orange hover:underline"
                >
                    View order
                </Link>
                <Link :href="route('sales.payments.index')" class="text-brand-orange hover:underline">Record payment</Link>
            </div>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <section class="admin-card lg:col-span-2 space-y-2">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-semibold text-brand-navy">{{ invoice.number }}</h2>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="statusMeta[invoice.status]?.class"
                    >
                        {{ invoice.status_label }}
                    </span>
                </div>
                <p class="text-sm text-gray-600">{{ invoice.customer_name }}</p>
                <p v-if="invoice.due_date" class="text-sm text-gray-500">Due {{ invoice.due_date }}</p>
            </section>
            <section class="admin-card space-y-1 text-sm">
                <p>Total: <strong>{{ invoice.currency }} {{ Number(invoice.grand_total).toFixed(2) }}</strong></p>
                <p>Paid: {{ Number(invoice.amount_paid).toFixed(2) }}</p>
                <p>Due: {{ Number(invoice.amount_due).toFixed(2) }}</p>
                <p class="text-xs text-gray-400">Created {{ formatDateTime(invoice.created_at) }}</p>
            </section>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Item</th>
                        <th class="pb-2">SKU</th>
                        <th class="pb-2">Qty</th>
                        <th class="pb-2">Unit</th>
                        <th class="pb-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in invoice.items" :key="item.id" class="border-t border-gray-50">
                        <td class="py-2">{{ item.name }}</td>
                        <td class="py-2 text-gray-500">{{ item.sku || '—' }}</td>
                        <td class="py-2">{{ Number(item.quantity) }}</td>
                        <td class="py-2">{{ Number(item.unit_price).toFixed(2) }}</td>
                        <td class="py-2 font-medium">{{ Number(item.line_total).toFixed(2) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AdminLayout>
</template>
