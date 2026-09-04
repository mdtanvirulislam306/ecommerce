<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ closings: Array, years: Array });
const flash = computed(() => usePage().props.flash);
const show = ref(false);
const form = useForm({ fiscal_year_id:'', closed_on: new Date().toISOString().slice(0,10), notes:'' });
const save = () => form.post(route('accounting.financial-closing.store'), { preserveScroll:true, onSuccess:()=>show.value=false });
</script>
<template>
  <Head title="Financial Closing" /><AdminLayout title="Financial Closing">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Closings</h2><PrimaryButton type="button" @click="show=true">Close year</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Fiscal year</th><th>Closed on</th><th>Notes</th></tr></thead>
    <tbody><tr v-for="row in closings" :key="row.id"><td>{{ row.fiscal_year }}</td><td>{{ row.closed_on }}</td><td>{{ row.notes || '—' }}</td></tr>
    <tr v-if="!closings.length"><td colspan="3" class="py-10 text-center text-gray-500">No closings yet.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">Close fiscal year</h3>
      <div><InputLabel value="Fiscal year" /><select v-model="form.fiscal_year_id" class="mt-1 w-full rounded-md border-gray-300 text-sm"><option value="">Select…</option><option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option></select></div>
      <div><InputLabel value="Closed on" /><TextInput v-model="form.closed_on" type="date" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Notes" /><TextInput v-model="form.notes" class="mt-1 block w-full" /></div>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Close</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
