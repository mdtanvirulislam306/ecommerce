<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    returnRecord: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    confirmed: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    cancelled: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const confirmReturn = () => {
    router.post(route('sales.returns.confirm', props.returnRecord.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="returnRecord.number" />

    <AdminLayout :title="returnRecord.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('sales.returns.index')"
                    class="text-sm font-medium text-brand-navy hover:text-brand-orange"
                >
                    ← Returns
                </Link>
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="statusMeta[returnRecord.status]?.class"
                >
                    {{ returnRecord.status_label }}
                </span>
            </div>
            <PrimaryButton v-if="returnRecord.can_confirm" type="button" @click="confirmReturn">
                Confirm & restock
            </PrimaryButton>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <section class="admin-card lg:col-span-2 space-y-2">
                <h2 class="text-sm font-semibold text-brand-navy">{{ returnRecord.number }}</h2>
                <p class="text-sm text-gray-600">{{ returnRecord.customer_name || '—' }}</p>
                <p v-if="returnRecord.notes" class="text-sm text-gray-500">{{ returnRecord.notes }}</p>
            </section>
            <section class="admin-card space-y-1">
                <p class="text-xs text-gray-500">Total</p>
                <p class="text-2xl font-semibold text-brand-navy">
                    {{ returnRecord.currency }} {{ Number(returnRecord.grand_total).toFixed(2) }}
                </p>
                <p class="text-xs text-gray-400">Created {{ formatDateTime(returnRecord.created_at) }}</p>
            </section>
        </div>

        <section class="admin-card !p-0 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-navy">Return lines</h2>
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
                        <tr v-for="item in returnRecord.items" :key="item.id" class="admin-data-table__row">
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
