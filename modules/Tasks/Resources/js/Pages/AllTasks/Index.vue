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
    tasks: { type: Object, required: true },
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
    title: '',
    description: '',
    due_at: '',
    status: 'open',
    assigned_to: null,
});

const deleteForm = useForm({});

const openCreate = () => { editing.value = null; form.reset(); form.clearErrors(); showModal.value = true; };
const openEdit = (row) => {
    editing.value = row;
    form.title = row.title ?? '';
    form.description = row.description ?? '';
    form.due_at = row.due_at ?? '';
    form.status = row.status ?? 'open';
    form.assigned_to = row.assigned_to ?? null;
    form.clearErrors();
    showModal.value = true;
};
const submit = () => {
    if (editing.value) {
        form.put(route('tasks.all-tasks.update', editing.value.id), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('tasks.all-tasks.store'), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    }
};
watch(search, (value) => {
    router.get(route('tasks.all-tasks.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="AllTasks" />
    <AdminLayout title="AllTasks">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Title</th><th class="pb-2">Status</th><th class="pb-2">Due</th><th class="pb-2" /></tr></thead>
                <tbody>
                    <tr v-for="row in tasks.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2">{{ row.title ?? '—' }}</td><td class="py-2">{{ row.status ?? '—' }}</td><td class="py-2">{{ row.due_at ?? '—' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="tasks" class="mt-4" />
        </section>
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit' : 'Add' }}</h2>
                <div><InputLabel value="Title" /><TextInput v-model="form.title" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.title" /></div>
<div><InputLabel value="Description" /><textarea v-model="form.description" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="3" /><InputError :message="form.errors.description" /></div>
<div><InputLabel value="Due at" /><TextInput v-model="form.due_at" type="datetime-local" class="mt-1 block w-full" /><InputError :message="form.errors.due_at" /></div>
<div><InputLabel value="Status" /><TextInput v-model="form.status" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.status" /></div>
<div><InputLabel value="Assigned to (user id)" /><TextInput v-model="form.assigned_to" type="number" class="mt-1 block w-full" /><InputError :message="form.errors.assigned_to" /></div>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" title="Delete this record?" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="deleteForm.delete(route('tasks.all-tasks.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })" />
    </AdminLayout>
</template>