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
const props = defineProps({ rows: Object, filters: Object });
const flash = computed(() => usePage().props.flash);
const show = ref(false); const editing = ref(null); const deleteTarget = ref(null);
const form = useForm({ name: '', code: '', is_active: true });
const openCreate = () => { editing.value=null; form.reset(); form.is_active=true; form.clearErrors(); show.value=true; };
const openEdit = (row) => { editing.value=row; form.name=row.name; form.code=row.code; form.is_active=row.is_active; form.clearErrors(); show.value=true; };
const save = () => { const o={preserveScroll:true,onSuccess:()=>show.value=false}; editing.value ? form.put(route('accounting.profit-centers.update', editing.value.id), o) : form.post(route('accounting.profit-centers.store'), o); };
const destroy = () => { if(!deleteTarget.value) return; router.delete(route('accounting.profit-centers.destroy', deleteTarget.value.id), { preserveScroll:true, onSuccess:()=>deleteTarget.value=null }); };
</script>
<template>
  <Head title="Profit Centers" /><AdminLayout title="Profit Centers">
    <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
    <div class="admin-data-table"><div class="admin-data-table__toolbar"><h2 class="text-sm font-semibold text-brand-navy">Profit Centers</h2><PrimaryButton type="button" @click="openCreate">Add</PrimaryButton></div>
    <table class="min-w-full"><thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
    <tbody><tr v-for="row in rows.data" :key="row.id"><td class="font-medium text-brand-navy">{{ row.name }}</td><td class="font-mono text-xs">{{ row.code }}</td><td>{{ row.is_active ? 'Active' : 'Inactive' }}</td>
    <td class="space-x-2 text-right"><button type="button" class="text-sm" @click="openEdit(row)">Edit</button><button type="button" class="text-sm text-red-600" @click="deleteTarget=row">Delete</button></td></tr>
    <tr v-if="!rows.data.length"><td colspan="4" class="py-10 text-center text-gray-500">No records.</td></tr></tbody></table></div>
    <Modal :show="show" @close="show=false"><form class="space-y-4 p-6" @submit.prevent="save"><h3 class="text-lg font-semibold">{{ editing ? 'Edit' : 'Add' }}</h3>
    <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /><InputError :message="form.errors.name" /></div>
    <div><InputLabel value="Code" /><TextInput v-model="form.code" class="mt-1 block w-full" /><InputError :message="form.errors.code" /></div>
    <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
    <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="show=false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div></form></Modal>
    <Modal :show="!!deleteTarget" @close="deleteTarget=null"><div class="p-6"><p class="mb-4">Delete {{ deleteTarget?.name }}?</p><div class="flex justify-end gap-2"><SecondaryButton @click="deleteTarget=null">Cancel</SecondaryButton><PrimaryButton @click="destroy">Delete</PrimaryButton></div></div></Modal>
  </AdminLayout>
</template>
