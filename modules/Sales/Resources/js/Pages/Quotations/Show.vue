<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    quotation: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const statusMeta = {
    draft: { class: 'bg-gray-100 text-gray-600' },
    sent: { class: 'bg-sky-50 text-sky-800' },
    accepted: { class: 'bg-emerald-50 text-emerald-700' },
    expired: { class: 'bg-red-50 text-red-700' },
};

const postAction = (routeName) => {
    router.post(route(routeName, props.quotation.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="quotation.number" />

    <AdminLayout :title="quotation.number">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('sales.quotations.all')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Quotations
            </Link>
            <div class="flex flex-wrap gap-2">
                <Link v-if="quotation.can_edit" :href="route('sales.quotations.edit', quotation.id)">
                    <SecondaryButton type="button">Edit</SecondaryButton>
                </Link>
                <SecondaryButton v-if="quotation.can_mark_sent" type="button" @click="postAction('sales.quotations.mark-sent')">
                    Mark sent
                </SecondaryButton>
                <SecondaryButton
                    v-if="quotation.can_mark_accepted"
                    type="button"
                    @click="postAction('sales.quotations.mark-accepted')"
                >
                    Accept
                </SecondaryButton>
                <SecondaryButton
                    v-if="quotation.can_mark_expired"
                    type="button"
                    @click="postAction('sales.quotations.mark-expired')"
                >
                    Expire
                </SecondaryButton>
                <PrimaryButton v-if="quotation.can_convert" type="button" @click="postAction('sales.quotations.convert')">
                    Convert to order
                </PrimaryButton>
                <Link
                    v-if="quotation.sales_order_id"
                    :href="route('sales.orders.show', quotation.sales_order_id)"
                    class="text-sm text-brand-orange hover:underline"
                >
                    View order →
                </Link>
            </div>
        </div>

        <div class="mb-6 grid gap-4 lg:grid-cols-3">
            <section class="admin-card lg:col-span-2 space-y-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-semibold text-brand-navy">{{ quotation.number }}</h2>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="statusMeta[quotation.status]?.class"
                    >
                        {{ quotation.status_label }}
                    </span>
                </div>
                <p class="text-sm text-gray-600">
                    Customer: {{ quotation.customer_name }}
                    <Link
                        v-if="quotation.customer_id"
                        :href="route('crm.customers.show', quotation.customer_id)"
                        class="ml-2 text-xs font-medium text-brand-orange hover:underline"
                    >
                        CRM
                    </Link>
                </p>
                <p v-if="quotation.customer_email" class="text-sm text-gray-500">{{ quotation.customer_email }}</p>
                <p v-if="quotation.valid_until" class="text-sm text-gray-500">Valid until {{ quotation.valid_until }}</p>
                <p v-if="quotation.notes" class="text-sm text-gray-500">{{ quotation.notes }}</p>
            </section>
            <section class="admin-card space-y-2">
                <p class="text-xs text-gray-500">Grand total</p>
                <p class="text-2xl font-semibold text-brand-navy">
                    {{ quotation.currency }} {{ Number(quotation.grand_total).toFixed(2) }}
                </p>
                <p class="text-xs text-gray-400">Created {{ formatDateTime(quotation.created_at) }}</p>
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
                    <tr v-for="item in quotation.items" :key="item.id" class="border-t border-gray-50">
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
