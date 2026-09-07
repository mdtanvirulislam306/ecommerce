<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    monthly: { type: Array, default: () => [] },
    top_registers: { type: Array, default: () => [] },
});

const money = (value) =>
    Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const cards = computed(() => [
    { label: 'Revenue today', value: money(props.summary.revenue_today), tone: 'text-brand-navy' },
    { label: 'Orders today', value: props.summary.orders_today, tone: 'text-brand-navy' },
    { label: 'Returns today', value: money(props.summary.returns_today), tone: 'text-amber-700' },
    { label: 'Open sessions', value: props.summary.open_sessions, tone: 'text-emerald-700' },
    { label: 'Cash in today', value: money(props.summary.cash_in_today), tone: 'text-emerald-700' },
    { label: 'Cash out today', value: money(props.summary.cash_out_today), tone: 'text-sky-800' },
]);

const maxMonthly = computed(() =>
    Math.max(1, ...props.monthly.map((row) => Number(row.amount || 0))),
);
</script>

<template>
    <Head title="POS Reports" />

    <AdminLayout title="POS Reports">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-brand-navy">POS performance</h1>
                <p class="text-sm text-gray-500">Today’s snapshot plus recent monthly trend.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    :href="route('pos.orders.index')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-brand-navy hover:bg-gray-50"
                >
                    View orders
                </Link>
                <Link
                    :href="route('pos.terminal')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-navy px-3 py-2 text-xs font-semibold text-white hover:bg-brand-navy/90"
                >
                    <ActionIcon name="terminal" />
                    Terminal
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div v-for="card in cards" :key="card.label" class="admin-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ card.label }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums" :class="card.tone">{{ card.value }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Monthly sales</h2>
                    <span class="text-xs text-gray-400">Last 6 months</span>
                </div>
                <div v-if="monthly.length" class="space-y-3">
                    <div v-for="row in monthly" :key="row.period">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ row.period }}</span>
                            <span class="tabular-nums text-gray-500">
                                {{ row.orders_count }} orders · {{ money(row.amount) }}
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-brand-teal"
                                :style="{ width: `${Math.max(6, (Number(row.amount) / maxMonthly) * 100)}%` }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-gray-400">No monthly sales yet.</p>
            </section>

            <section class="admin-card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-brand-navy">Top registers</h2>
                    <Link :href="route('pos.registers.index')" class="text-xs font-medium text-brand-orange hover:underline">
                        Manage
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="pb-2 font-medium">Register</th>
                                <th class="pb-2 text-right font-medium">Orders</th>
                                <th class="pb-2 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in top_registers" :key="row.name" class="border-t border-gray-50">
                                <td class="py-2.5 font-medium text-brand-navy">{{ row.name }}</td>
                                <td class="py-2.5 text-right tabular-nums text-gray-600">{{ row.orders_count }}</td>
                                <td class="py-2.5 text-right tabular-nums font-semibold">{{ money(row.amount) }}</td>
                            </tr>
                            <tr v-if="!top_registers.length">
                                <td colspan="3" class="py-8 text-center text-gray-400">No register sales yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
