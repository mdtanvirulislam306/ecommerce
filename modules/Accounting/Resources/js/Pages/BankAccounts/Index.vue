<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ rows: Object, filters: Object });
const flash = computed(() => usePage().props.flash);
const show = ref(false); const editing = ref(null);
const form = useForm({ name:'', code:'', bank_name:'', account_number:'', currency:'BDT', opening_balance:0, is_active:true });
const openCreate = () => { editing.value=null; form.reset(); form.currency='BDT'; form.is_active=true; form.clearErrors(); show.value=true; };
const openEdit = (row) => { editing.value=row; Object.assign(form, { name:row.name, code:row.code, bank_name:row.bank_name||'', account_number:row.account_number||'', currency:row.currency, opening_balance:row.opening_balance, is_active:row.is_active }); form.clearErrors(); show.value=true; };
const save = () => { const o={preserveScroll:true,onSuccess:()=>show.value=false}; editing.value ? form.put(route('accounting.bank-accounts.update', editing.value.id), o) : form.post(route('accounting.bank-accounts.store'), o); };
</script>
<template>
  <Head title="Bank Accounts" /><AdminLayout title="Bank Accounts">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Bank accounts</h2><PrimaryButton type="button" @click="openCreate">Add</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Name</th><th>Code</th><th>Bank</th><th>Opening</th><th>Status</th><th></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td class="font-medium">{{ row.name }}</td><td class="font-mono text-xs">{{ row.code }}</td><td>{{ row.bank_name || '—' }}</td><td>{{ Number(row.opening_balance).toFixed(2) }}</td><td>{{ row.is_active ? 'Active' : 'Inactive' }}</td>
    <td class="text-right space-x-2"><button type="button" class="text-sm" @click="openEdit(row)">Edit</button><button type="button" class="text-sm text-red-600" @click="router.delete(route('accounting.bank-accounts.destroy', row.id), {preserveScroll:true})">Delete</button></td></tr>
    <tr v-if="!rows.data.length"><td colspan="6" class="py-10 text-center text-gray-500">No bank accounts.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">{{ editing?'Edit':'Add' }} bank account</h3>
      <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /><InputError :message="form.errors.name" /></div>
      <div><InputLabel value="Code" /><TextInput v-model="form.code" class="mt-1 block w-full" /><InputError :message="form.errors.code" /></div>
      <div><InputLabel value="Bank name" /><TextInput v-model="form.bank_name" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Account number" /><TextInput v-model="form.account_number" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Opening balance" /><TextInput v-model="form.opening_balance" type="number" step="0.01" class="mt-1 block w-full" /></div>
      <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
