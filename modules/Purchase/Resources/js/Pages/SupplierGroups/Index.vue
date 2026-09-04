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
    groups: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
    sort_order: 0,
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (group) => {
    editing.value = group;
    form.name = group.name;
    form.code = group.code;
    form.description = group.description || '';
    form.is_active = group.is_active;
    form.sort_order = group.sort_order;
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    };

    if (editing.value) {
        form.put(route('purchase.suppliers.groups.update', editing.value.id), options);
    } else {
        form.post(route('purchase.suppliers.groups.store'), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('purchase.suppliers.groups.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const visitIndex = () => {
    router.get(
        route('purchase.suppliers.groups'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Supplier Groups" />

    <AdminLayout title="Supplier Groups">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Supplier groups</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <PrimaryButton type="button" @click="openCreate">Add group</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Suppliers</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="group in groups.data" :key="group.id">
                            <td class="font-medium text-brand-navy">{{ group.name }}</td>
                            <td class="font-mono text-xs">{{ group.code }}</td>
                            <td>{{ group.suppliers_count }}</td>
                            <td>{{ group.is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-right space-x-2">
                                <button type="button" class="text-sm text-brand-navy hover:text-brand-orange" @click="openEdit(group)">Edit</button>
                                <button type="button" class="text-sm text-red-600" @click="deleteTarget = group">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!groups.data.length">
                            <td colspan="5" class="py-10 text-center text-gray-500">No supplier groups yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePagination :paginator="groups" :per-page="perPage" :per-page-options="perPageOptions" @change-page="(p) => router.get(route('purchase.suppliers.groups'), { search: search || undefined, per_page: perPage, page: p }, { preserveState: true, replace: true })" @change-per-page="(v) => { perPage = v; visitIndex(); }" />
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit group' : 'Add group' }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="save">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Code" />
                        <TextInput v-model="form.code" class="mt-1 block w-full" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Description" />
                        <TextInput v-model="form.description" class="mt-1 block w-full" />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <Checkbox v-model:checked="form.is_active" />
                        Active
                    </label>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal :show="!!deleteTarget" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="confirmDelete" />
    </AdminLayout>
</template>
