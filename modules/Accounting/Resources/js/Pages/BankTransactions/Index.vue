<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ rows: Object, accounts: Array, filters: Object });
const flash = computed(() => usePage().props.flash);
const show = ref(false);
const form = useForm({ bank_account_id:'', txn_date: new Date().toISOString().slice(0,10), type:'deposit', amount:'', reference:'', memo:'', is_reconciled:false });
const save = () => form.post(route('accounting.bank-transactions.store'), { preserveScroll:true, onSuccess:()=>{ show.value=false; form.reset('amount','reference','memo'); form.type='deposit'; form.txn_date=new Date().toISOString().slice(0,10);} });
</script>
<template>
  <Head title="Bank Transactions" /><AdminLayout title="Bank Transactions">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Transactions</h2><PrimaryButton type="button" @click="show=true">Add</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Amount</th><th>Reference</th><th></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td>{{ row.txn_date }}</td><td>{{ row.account_name }}</td><td class="capitalize">{{ row.type }}</td><td>{{ Number(row.amount).toFixed(2) }}</td><td>{{ row.reference || '—' }}</td>
    <td class="text-right"><button type="button" class="text-sm text-red-600" @click="router.delete(route('accounting.bank-transactions.destroy', row.id), {preserveScroll:true})">Delete</button></td></tr>
    <tr v-if="!rows.data.length"><td colspan="6" class="py-10 text-center text-gray-500">No transactions.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">New transaction</h3>
      <div><InputLabel value="Account" /><select v-model="form.bank_account_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Select…</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option></select><InputError :message="form.errors.bank_account_id" /></div>
      <div><InputLabel value="Date" /><TextInput v-model="form.txn_date" type="date" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Type" /><select v-model="form.type" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="deposit">Deposit</option><option value="withdrawal">Withdrawal</option><option value="transfer">Transfer</option></select></div>
      <div><InputLabel value="Amount" /><TextInput v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" /><InputError :message="form.errors.amount" /></div>
      <div><InputLabel value="Reference" /><TextInput v-model="form.reference" class="mt-1 block w-full" /></div>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
