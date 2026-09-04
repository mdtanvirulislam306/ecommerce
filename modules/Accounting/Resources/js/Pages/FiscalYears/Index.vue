<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ rows: Object, filters: Object });
const flash = computed(() => usePage().props.flash);
const show = ref(false);
const form = useForm({ name:'', starts_on:'', ends_on:'', is_current:false });
const save = () => form.post(route('accounting.fiscal-years.store'), { preserveScroll:true, onSuccess:()=>show.value=false });
</script>
<template>
  <Head title="Fiscal Years" /><AdminLayout title="Fiscal Years">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Fiscal years</h2><PrimaryButton type="button" @click="show=true">Add</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Name</th><th>Starts</th><th>Ends</th><th>Current</th><th>Closed</th><th></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td>{{ row.name }}</td><td>{{ row.starts_on }}</td><td>{{ row.ends_on }}</td><td>{{ row.is_current ? 'Yes' : 'No' }}</td><td>{{ row.is_closed ? 'Yes' : 'No' }}</td>
    <td class="text-right"><button type="button" class="text-sm text-red-600" @click="router.delete(route('accounting.fiscal-years.destroy', row.id), {preserveScroll:true})">Delete</button></td></tr>
    <tr v-if="!rows.data.length"><td colspan="6" class="py-10 text-center text-gray-500">No fiscal years.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">Add fiscal year</h3>
      <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Starts on" /><TextInput v-model="form.starts_on" type="date" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Ends on" /><TextInput v-model="form.ends_on" type="date" class="mt-1 block w-full" /></div>
      <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_current" /> Current year</label>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
