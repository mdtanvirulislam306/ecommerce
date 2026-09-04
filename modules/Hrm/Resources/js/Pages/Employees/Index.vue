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
    employees: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    department: '',
    designation: '',
    hired_at: '',
    is_active: true,
    notes: '',
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.name = row.name;
    form.code = row.code;
    form.email = row.email || '';
    form.phone = row.phone || '';
    form.department = row.department || '';
    form.designation = row.designation || '';
    form.hired_at = row.hired_at || '';
    form.is_active = row.is_active;
    form.notes = row.notes || '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('hrm.employees.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('hrm.employees.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route('hrm.employees.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Employees" />

    <AdminLayout title="Employees">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search employees…" />
            <PrimaryButton type="button" @click="openCreate">Add employee</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Code</th>
                        <th class="pb-2">Name</th>
                        <th class="pb-2">Department</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in employees.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 text-gray-500">{{ row.code }}</td>
                        <td class="py-2 font-medium text-brand-navy">{{ row.name }}</td>
                        <td class="py-2">{{ row.department || '—' }}</td>
                        <td class="py-2">{{ row.is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="employees" class="mt-4" />
        </section>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit employee' : 'Add employee' }}</h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Code" />
                        <TextInput v-model="form.code" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Department" />
                        <TextInput v-model="form.department" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Email" />
                        <TextInput v-model="form.email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="form.phone" class="mt-1 block w-full" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model:checked="form.is_active" />
                    Active
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete employee?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('hrm.employees.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
