<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ rows: Object, assets: Array });
const flash = computed(() => usePage().props.flash);
const show = ref(false);
const form = useForm({ fixed_asset_id:'', period_date: new Date().toISOString().slice(0,10), amount:'', notes:'' });
const save = () => form.post(route('accounting.fixed-assets.depreciation.store'), { preserveScroll:true, onSuccess:()=>show.value=false });
</script>
<template>
  <Head title="Depreciation" /><AdminLayout title="Depreciation Stub">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Depreciation entries</h2><PrimaryButton type="button" @click="show=true">Record</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Asset</th><th>Period</th><th>Amount</th><th>Notes</th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td>{{ row.asset_name }}</td><td>{{ row.period_date }}</td><td>{{ Number(row.amount).toFixed(2) }}</td><td>{{ row.notes || '—' }}</td></tr>
    <tr v-if="!rows.data.length"><td colspan="4" class="py-10 text-center text-gray-500">No depreciation entries.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">Record depreciation</h3>
      <div><InputLabel value="Asset" /><select v-model="form.fixed_asset_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Select…</option><option v-for="a in assets" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
      <div><InputLabel value="Period date" /><TextInput v-model="form.period_date" type="date" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Amount" /><TextInput v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" /></div>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
