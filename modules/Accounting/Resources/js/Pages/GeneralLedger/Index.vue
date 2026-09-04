<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
const props = defineProps({ lines: Object, accounts: Array, filters: Object });
const accountId = ref(props.filters.ledger_account_id || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');
const apply = () => router.get(route('accounting.general-ledger'), { ledger_account_id: accountId.value || undefined, from: from.value || undefined, to: to.value || undefined }, { preserveState: true, replace: true });
</script>
<template>
  <Head title="General Ledger" /><AdminLayout title="General Ledger">
    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="accountId" class="rounded-md border-gray-300 text-sm" @change="apply"><option value="">All accounts</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} — {{ a.name }}</option></select>
      <input v-model="from" type="date" class="rounded-md border-gray-300 text-sm" @change="apply" />
      <input v-model="to" type="date" class="rounded-md border-gray-300 text-sm" @change="apply" />
    </div>
    <div class="admin-card overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Date</th><th class="pb-2">Entry</th><th class="pb-2">Account</th><th class="pb-2 text-right">Debit</th><th class="pb-2 text-right">Credit</th><th class="pb-2">Memo</th></tr></thead>
    <tbody><tr v-for="line in lines.data" :key="line.id" class="border-t border-gray-50"><td class="py-2">{{ line.entry_date }}</td><td class="py-2">{{ line.entry_number }}</td><td class="py-2">{{ line.account_code }} {{ line.account_name }}</td><td class="py-2 text-right">{{ Number(line.debit).toFixed(2) }}</td><td class="py-2 text-right">{{ Number(line.credit).toFixed(2) }}</td><td class="py-2">{{ line.memo || line.entry_memo || '—' }}</td></tr>
    <tr v-if="!lines.data.length"><td colspan="6" class="py-10 text-center text-gray-500">No ledger lines.</td></tr></tbody></table>
    <TablePagination :paginator="lines" :per-page="filters.per_page || 50" :per-page-options="[25,50,100]" @change-page="(p)=>router.get(route('accounting.general-ledger'),{...filters,page:p},{preserveState:true,replace:true})" @change-per-page="(v)=>router.get(route('accounting.general-ledger'),{...filters,per_page:v},{preserveState:true,replace:true})" /></div>
  </AdminLayout>
</template>
