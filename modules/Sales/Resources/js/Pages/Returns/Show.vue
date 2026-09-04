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

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('sales.returns.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Returns
            </Link>
            <PrimaryButton v-if="returnRecord.can_confirm" type="button" @click="confirmReturn">
                Confirm & restock
            </PrimaryButton>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <section class="admin-card lg:col-span-2 space-y-2">
                <h2 class="text-sm font-semibold text-brand-navy">{{ returnRecord.number }}</h2>
                <p class="text-sm text-gray-600">{{ returnRecord.status_label }}</p>
                <p class="text-sm text-gray-500">{{ returnRecord.customer_name || '—' }}</p>
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
                    <tr v-for="item in returnRecord.items" :key="item.id" class="border-t border-gray-50">
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
