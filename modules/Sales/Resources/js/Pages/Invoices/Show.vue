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
    due: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    partial: { class: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200' },
    paid: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    overdue: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};
</script>

<template>
    <Head :title="invoice.number" />

    <AdminLayout :title="invoice.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('sales.invoices.all')"
                    class="text-sm font-medium text-brand-navy hover:text-brand-orange"
                >
                    ← Invoices
                </Link>
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusMeta[invoice.status]?.class"
                >
                    {{ invoice.status_label }}
                </span>
            </div>
            <div class="flex flex-wrap gap-3 text-sm">
                <a
                    :href="route('sales.invoices.print', invoice.id)"
                    target="_blank"
                    rel="noopener"
                    class="font-medium text-brand-orange hover:underline"
                >
                    Print invoice
                </a>
                <Link
                    v-if="invoice.sales_order_id"
                    :href="route('sales.orders.show', invoice.sales_order_id)"
                    class="font-medium text-brand-orange hover:underline"
                >
                    View order
                </Link>
                <Link :href="route('sales.payments.index')" class="font-medium text-brand-orange hover:underline">
                    Record payment
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <section class="admin-card lg:col-span-2 space-y-2">
                <h2 class="text-sm font-semibold text-brand-navy">{{ invoice.number }}</h2>
                <p class="text-sm text-gray-600">{{ invoice.customer_name }}</p>
                <p v-if="invoice.due_date" class="text-sm text-gray-500">Due {{ invoice.due_date }}</p>
            </section>
            <section class="admin-card space-y-1 text-sm">
                <p>
                    Total:
                    <strong class="text-brand-navy">
                        {{ invoice.currency }} {{ Number(invoice.grand_total).toFixed(2) }}
                    </strong>
                </p>
                <p>Paid: {{ Number(invoice.amount_paid).toFixed(2) }}</p>
                <p>Due: {{ Number(invoice.amount_due).toFixed(2) }}</p>
                <p class="text-xs text-gray-400">Created {{ formatDateTime(invoice.created_at) }}</p>
            </section>
        </div>

        <section class="admin-card !p-0 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-navy">Line items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Item</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ item.name }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ item.sku || '—' }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ Number(item.quantity) }}</td>
                            <td class="admin-data-table__cell tabular-nums">{{ Number(item.unit_price).toFixed(2) }}</td>
                            <td class="admin-data-table__cell text-right tabular-nums font-medium">
                                {{ Number(item.line_total).toFixed(2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
