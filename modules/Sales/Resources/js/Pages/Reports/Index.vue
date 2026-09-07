<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

const cards = computed(() => [
    {
        label: 'Quotations',
        value: props.stats.quotations.total,
        hint: `Accepted ${props.stats.quotations.accepted} · Value ${Number(props.stats.quotations.value).toFixed(0)}`,
        href: 'sales.quotations.all',
        accent: 'border-l-sky-500',
        valueClass: 'text-sky-700',
    },
    {
        label: 'Orders',
        value: props.stats.orders.confirmed,
        hint: `Of ${props.stats.orders.total} · Revenue ${Number(props.stats.orders.revenue).toFixed(0)}`,
        href: 'sales.orders.all',
        query: { status: 'confirmed' },
        accent: 'border-l-emerald-500',
        valueClass: 'text-emerald-700',
    },
    {
        label: 'Outstanding',
        value: Number(props.stats.invoices.outstanding || 0).toFixed(0),
        hint: `Due ${props.stats.invoices.due} · Overdue ${props.stats.invoices.overdue}`,
        href: 'sales.invoices.all',
        query: { status: 'due' },
        accent: 'border-l-amber-500',
        valueClass: 'text-amber-700',
    },
    {
        label: 'Collected',
        value: Number(props.stats.payments.total || 0).toFixed(0),
        hint: `${props.stats.payments.count} payments`,
        href: 'sales.payments.index',
        accent: 'border-l-brand-navy',
        valueClass: 'text-brand-navy',
    },
    {
        label: 'Returns',
        value: props.stats.returns.confirmed,
        hint: `Of ${props.stats.returns.count} · ${Number(props.stats.returns.value).toFixed(0)}`,
        href: 'sales.returns.index',
        accent: 'border-l-orange-500',
        valueClass: 'text-brand-orange',
    },
    {
        label: 'Credit notes',
        value: props.stats.credit_notes.count,
        hint: `Issued ${Number(props.stats.credit_notes.total).toFixed(0)}`,
        href: 'sales.credit-notes.index',
        accent: 'border-l-red-400',
        valueClass: 'text-red-700',
    },
]);
</script>

<template>
    <Head title="Sales Reports" />

    <AdminLayout title="Sales Reports">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <p class="max-w-xl text-sm text-gray-500">
                Live totals from quotations, orders, invoices, payments, returns, and credit notes. Open any card for the
                list.
            </p>
            <Link :href="route('sales.overview')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">
                ← Overview
            </Link>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="card in cards"
                :key="card.label"
                :href="route(card.href, card.query || {})"
                class="admin-card border-l-4 p-5 transition hover:border-brand-teal/30 hover:shadow-md"
                :class="card.accent"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight" :class="card.valueClass">{{ card.value }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
            </Link>
        </div>
    </AdminLayout>
</template>
