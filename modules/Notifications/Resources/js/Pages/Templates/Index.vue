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
import Checkbox from '@/Components/Checkbox.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    templates: { type: Object, required: true },
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
    name: '',
    channel: 'email',
    subject: '',
    body: '',
    is_active: true,
});

const deleteForm = useForm({});

const openCreate = () => { editing.value = null; form.reset(); form.clearErrors(); showModal.value = true; };
const openEdit = (row) => {
    editing.value = row;
    form.name = row.name ?? '';
    form.channel = row.channel ?? 'email';
    form.subject = row.subject ?? '';
    form.body = row.body ?? '';
    form.is_active = row.is_active ?? true;
    form.clearErrors();
    showModal.value = true;
};
const submit = () => {
    if (editing.value) {
        form.put(route('notifications.templates.update', editing.value.id), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('notifications.templates.store'), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    }
};
watch(search, (value) => {
    router.get(route('notifications.templates.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="Templates" />
    <AdminLayout title="Templates">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Name</th><th class="pb-2">Channel</th><th class="pb-2">Subject</th><th class="pb-2">Active</th><th class="pb-2" /></tr></thead>
                <tbody>
                    <tr v-for="row in templates.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2">{{ row.name ?? '—' }}</td><td class="py-2">{{ row.channel ?? '—' }}</td><td class="py-2">{{ row.subject ?? '—' }}</td><td class="py-2">{{ row.is_active ?? '—' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="templates" class="mt-4" />
        </section>
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit' : 'Add' }}</h2>
                <div><InputLabel value="Name" /><TextInput v-model="form.name" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.name" /></div>
<div><InputLabel value="Channel" /><TextInput v-model="form.channel" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.channel" /></div>
<div><InputLabel value="Subject" /><TextInput v-model="form.subject" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.subject" /></div>
<div><InputLabel value="Body" /><textarea v-model="form.body" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="3" /><InputError :message="form.errors.body" /></div>
<label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" title="Delete this record?" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="deleteForm.delete(route('notifications.templates.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })" />
    </AdminLayout>
</template>