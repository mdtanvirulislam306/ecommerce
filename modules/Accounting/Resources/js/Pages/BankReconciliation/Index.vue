<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
defineProps({ rows: Object });
const flash = computed(() => usePage().props.flash);
</script>
<template>
  <Head title="Bank Reconciliation" /><AdminLayout title="Bank Reconciliation">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-card overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Date</th><th class="pb-2">Account</th><th class="pb-2">Type</th><th class="pb-2 text-right">Amount</th><th class="pb-2"></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id" class="border-t"><td class="py-2">{{ row.txn_date }}</td><td class="py-2">{{ row.account_name }}</td><td class="py-2 capitalize">{{ row.type }}</td><td class="py-2 text-right">{{ Number(row.amount).toFixed(2) }}</td>
    <td class="py-2 text-right"><PrimaryButton type="button" @click="router.post(route('accounting.bank-reconciliation.reconcile', row.id), {}, {preserveScroll:true})">Reconcile</PrimaryButton></td></tr>
    <tr v-if="!rows.data.length"><td colspan="5" class="py-10 text-center text-gray-500">Nothing to reconcile.</td></tr></tbody></table></div>
  </AdminLayout>
</template>
