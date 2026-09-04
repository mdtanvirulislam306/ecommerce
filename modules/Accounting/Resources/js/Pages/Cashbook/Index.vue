<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { Head, router } from '@inertiajs/vue3';
defineProps({ lines: Object, filters: Object });
</script>
<template>
  <Head title="Cashbook" /><AdminLayout title="Cashbook">
    <div class="admin-card overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Date</th><th class="pb-2">Entry</th><th class="pb-2">Account</th><th class="pb-2 text-right">In</th><th class="pb-2 text-right">Out</th><th class="pb-2">Memo</th></tr></thead>
    <tbody><tr v-for="line in lines.data" :key="line.id" class="border-t border-gray-50"><td class="py-2">{{ line.entry_date }}</td><td class="py-2">{{ line.entry_number }}</td><td class="py-2">{{ line.account_name }}</td><td class="py-2 text-right">{{ Number(line.debit).toFixed(2) }}</td><td class="py-2 text-right">{{ Number(line.credit).toFixed(2) }}</td><td class="py-2">{{ line.memo || '—' }}</td></tr>
    <tr v-if="!lines.data.length"><td colspan="6" class="py-10 text-center text-gray-500">No cashbook activity.</td></tr></tbody></table>
    <TablePagination :paginator="lines" :per-page="filters.per_page || 50" :per-page-options="[25,50,100]" @change-page="(p)=>router.get(route('accounting.cashbook'),{page:p},{preserveState:true})" @change-per-page="(v)=>router.get(route('accounting.cashbook'),{per_page:v},{preserveState:true})" /></div>
  </AdminLayout>
</template>
