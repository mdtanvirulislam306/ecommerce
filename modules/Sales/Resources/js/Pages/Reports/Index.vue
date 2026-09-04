<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Sales Reports" />

    <AdminLayout title="Sales Reports">
        <p class="mb-5 text-sm text-gray-500">
            Live totals from quotations, orders, invoices, payments, returns, and credit notes.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <section class="admin-card space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Quotations</h2>
                    <Link :href="route('sales.quotations.all')" class="text-xs text-brand-orange hover:underline">View</Link>
                </div>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.quotations.total }}</p>
                <p class="text-xs text-gray-500">
                    Draft {{ stats.quotations.draft }} · Sent {{ stats.quotations.sent }} · Accepted
                    {{ stats.quotations.accepted }} · Expired {{ stats.quotations.expired }}
                </p>
                <p class="text-sm text-gray-600">Value {{ Number(stats.quotations.value).toFixed(2) }}</p>
            </section>

            <section class="admin-card space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Orders</h2>
                    <Link :href="route('sales.orders.all')" class="text-xs text-brand-orange hover:underline">View</Link>
                </div>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.orders.confirmed }}</p>
                <p class="text-xs text-gray-500">Confirmed of {{ stats.orders.total }} total</p>
                <p class="text-sm text-gray-600">Revenue {{ Number(stats.orders.revenue).toFixed(2) }}</p>
            </section>

            <section class="admin-card space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Invoices</h2>
                    <Link :href="route('sales.invoices.all')" class="text-xs text-brand-orange hover:underline">View</Link>
                </div>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.invoices.total }}</p>
                <p class="text-xs text-gray-500">
                    Due {{ stats.invoices.due }} · Partial {{ stats.invoices.partial }} · Paid {{ stats.invoices.paid }} ·
                    Overdue {{ stats.invoices.overdue }}
                </p>
                <p class="text-sm text-gray-600">Outstanding {{ Number(stats.invoices.outstanding).toFixed(2) }}</p>
            </section>

            <section class="admin-card space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Payments</h2>
                    <Link :href="route('sales.payments.index')" class="text-xs text-brand-orange hover:underline">View</Link>
                </div>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.payments.count }}</p>
                <p class="text-sm text-gray-600">Collected {{ Number(stats.payments.total).toFixed(2) }}</p>
            </section>

            <section class="admin-card space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Returns</h2>
                    <Link :href="route('sales.returns.index')" class="text-xs text-brand-orange hover:underline">View</Link>
                </div>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.returns.confirmed }}</p>
                <p class="text-xs text-gray-500">Confirmed of {{ stats.returns.count }} total</p>
                <p class="text-sm text-gray-600">Value {{ Number(stats.returns.value).toFixed(2) }}</p>
            </section>

            <section class="admin-card space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Credit notes</h2>
                    <Link :href="route('sales.credit-notes.index')" class="text-xs text-brand-orange hover:underline">
                        View
                    </Link>
                </div>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.credit_notes.count }}</p>
                <p class="text-sm text-gray-600">Issued {{ Number(stats.credit_notes.total).toFixed(2) }}</p>
            </section>
        </div>
    </AdminLayout>
</template>
