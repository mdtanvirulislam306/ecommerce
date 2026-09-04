<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ rows: Array, total: String });
</script>
<template>
  <Head title="Expenses" /><AdminLayout title="Expenses">
    <div class="mb-4 admin-card"><div class="text-xs uppercase text-gray-500">Expense total</div><div class="text-2xl font-semibold text-brand-navy">{{ total }}</div></div>
    <div class="admin-card overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Code</th><th class="pb-2">Account</th><th class="pb-2 text-right">Balance</th></tr></thead>
    <tbody><tr v-for="row in rows" :key="row.id" class="border-t border-gray-50"><td class="py-2 font-mono text-xs">{{ row.code }}</td><td class="py-2">{{ row.name }}</td><td class="py-2 text-right">{{ Number(row.balance).toFixed(2) }}</td></tr>
    <tr v-if="!rows.length"><td colspan="3" class="py-10 text-center text-gray-500">No expense activity.</td></tr></tbody></table></div>
  </AdminLayout>
</template>
