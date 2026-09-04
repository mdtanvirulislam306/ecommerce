<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ rows: Object, accounts: Array, filters: Object });
const flash = computed(() => usePage().props.flash);
const show = ref(false);
const form = useForm({ name:'', period: new Date().toISOString().slice(0,7), ledger_account_id:'', amount:'', notes:'' });
const save = () => form.post(route('accounting.budgets.store'), { preserveScroll:true, onSuccess:()=>show.value=false });
</script>
<template>
  <Head title="Budgets" /><AdminLayout title="Budgets">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Budgets</h2><PrimaryButton type="button" @click="show=true">Add</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Name</th><th>Period</th><th>Account</th><th>Amount</th><th></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td>{{ row.name }}</td><td>{{ row.period }}</td><td>{{ row.account_name || '—' }}</td><td>{{ Number(row.amount).toFixed(2) }}</td>
    <td class="text-right"><button type="button" class="text-sm text-red-600" @click="router.delete(route('accounting.budgets.destroy', row.id), {preserveScroll:true})">Delete</button></td></tr>
    <tr v-if="!rows.data.length"><td colspan="5" class="py-10 text-center text-gray-500">No budgets.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">Add budget</h3>
      <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Period" /><TextInput v-model="form.period" class="mt-1 block w-full" placeholder="YYYY-MM" /></div>
      <div><InputLabel value="Account" /><select v-model="form.ledger_account_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Optional</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} {{ a.name }}</option></select></div>
      <div><InputLabel value="Amount" /><TextInput v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" /></div>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
