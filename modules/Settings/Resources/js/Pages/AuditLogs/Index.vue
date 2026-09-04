<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    action: '',
    subject_type: '',
    subject_id: null,
    ip_address: '',
});

const deleteForm = useForm({});

const openCreate = () => { editing.value = null; form.reset(); form.clearErrors(); showModal.value = true; };
const openEdit = (row) => {
    editing.value = row;
    form.action = row.action ?? '';
    form.subject_type = row.subject_type ?? '';
    form.subject_id = row.subject_id ?? null;
    form.ip_address = row.ip_address ?? '';
    form.clearErrors();
    showModal.value = true;
};
const submit = () => {
    if (editing.value) {
        form.put(route('settings.audit-logs.update', editing.value.id), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('settings.audit-logs.store'), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    }
};
watch(search, (value) => {
    router.get(route('settings.audit-logs.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="AuditLogs" />
    <AdminLayout title="AuditLogs">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Action</th><th class="pb-2">Subject</th><th class="pb-2">IP</th><th class="pb-2">When</th><th class="pb-2" /></tr></thead>
                <tbody>
                    <tr v-for="row in logs.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2">{{ row.action ?? '—' }}</td><td class="py-2">{{ row.subject_type ?? '—' }}</td><td class="py-2">{{ row.ip_address ?? '—' }}</td><td class="py-2">{{ row.created_at ?? '—' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="logs" class="mt-4" />
        </section>
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit' : 'Add' }}</h2>
                <div><InputLabel value="Action" /><TextInput v-model="form.action" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.action" /></div>
<div><InputLabel value="Subject type" /><TextInput v-model="form.subject_type" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.subject_type" /></div>
<div><InputLabel value="Subject ID" /><TextInput v-model="form.subject_id" type="number" class="mt-1 block w-full" /><InputError :message="form.errors.subject_id" /></div>
<div><InputLabel value="IP" /><TextInput v-model="form.ip_address" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.ip_address" /></div>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" title="Delete this record?" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="deleteForm.delete(route('settings.audit-logs.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })" />
    </AdminLayout>
</template>