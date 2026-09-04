<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
defineProps({ rows: Object, filters: Object });
const flash = computed(() => usePage().props.flash);
const show = ref(false);
const form = useForm({ name:'', code:'', useful_life_years:5, depreciation_rate:0 });
const save = () => form.post(route('accounting.fixed-assets.categories.store'), { preserveScroll:true, onSuccess:()=>show.value=false });
</script>
<template>
  <Head title="Asset Categories" /><AdminLayout title="Fixed Asset Categories">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold">Categories</h2><PrimaryButton type="button" @click="show=true">Add</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Name</th><th>Code</th><th>Life (yrs)</th><th>Rate</th><th></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td>{{ row.name }}</td><td class="font-mono text-xs">{{ row.code }}</td><td>{{ row.useful_life_years }}</td><td>{{ row.depreciation_rate }}</td>
    <td class="text-right"><button type="button" class="text-sm text-red-600" @click="router.delete(route('accounting.fixed-assets.categories.destroy', row.id), {preserveScroll:true})">Delete</button></td></tr>
    <tr v-if="!rows.data.length"><td colspan="5" class="py-10 text-center text-gray-500">No categories.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-3 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">Add category</h3>
      <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Code" /><TextInput v-model="form.code" class="mt-1 block w-full" /></div>
      <div><InputLabel value="Useful life years" /><TextInput v-model="form.useful_life_years" type="number" class="mt-1 block w-full" /></div>
      <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
  </AdminLayout>
</template>
