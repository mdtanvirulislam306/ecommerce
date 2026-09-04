<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ rows: Array, total: String });
</script>
<template>
  <Head title="Receivables" /><AdminLayout title="Receivables">
    <div class="mb-4 admin-card"><div class="text-xs uppercase text-gray-500">Outstanding</div><div class="text-2xl font-semibold text-brand-navy">{{ total }}</div></div>
    <div class="admin-card overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Doc</th><th class="pb-2">Party</th><th class="pb-2 text-right">Amount</th><th class="pb-2 text-right">Paid</th><th class="pb-2 text-right">Balance</th><th class="pb-2">Status</th></tr></thead>
    <tbody><tr v-for="row in rows" :key="row.number" class="border-t border-gray-50"><td class="py-2">{{ row.number }}</td><td class="py-2">{{ row.party }}</td><td class="py-2 text-right">{{ row.amount }}</td><td class="py-2 text-right">{{ row.paid }}</td><td class="py-2 text-right">{{ row.balance }}</td><td class="py-2 capitalize">{{ row.status }}</td></tr>
    <tr v-if="!rows.length"><td colspan="6" class="py-10 text-center text-gray-500">No receivables.</td></tr></tbody></table></div>
  </AdminLayout>
</template>
