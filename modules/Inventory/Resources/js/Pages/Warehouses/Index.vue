<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    warehouses: { type: Object, required: true },
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
const meta = computed(() => paginationMeta(props.warehouses));

const form = useForm({
    name: '',
    code: '',
    address: '',
    is_active: true,
    is_default: false,
    sort_order: 0,
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.is_default = false;
    form.sort_order = 0;
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (warehouse) => {
    editing.value = warehouse;
    form.name = warehouse.name;
    form.code = warehouse.code;
    form.address = warehouse.address || '';
    form.is_active = warehouse.is_active;
    form.is_default = warehouse.is_default;
    form.sort_order = warehouse.sort_order;
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    if (editing.value) {
        form.put(route('inventory.warehouses.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('inventory.warehouses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    deleteForm.delete(route('inventory.warehouses.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const visitIndex = () => {
    router.get(
        route('inventory.warehouses.index'),
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
    <Head title="Warehouses" />

    <AdminLayout title="Warehouses">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Warehouses</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search warehouses…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add warehouse
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="warehouse in warehouses.data" :key="warehouse.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ warehouse.name }}</div>
                                <div v-if="warehouse.address" class="mt-0.5 text-xs text-gray-400">{{ warehouse.address }}</div>
                            </td>
                            <td class="admin-data-table__cell font-mono text-xs text-gray-600">{{ warehouse.code }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="warehouse.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span
                                    v-if="warehouse.is_default"
                                    class="ml-1 inline-flex rounded-full bg-brand-orange/10 px-2.5 py-0.5 text-xs font-medium text-brand-orange"
                                >
                                    Default
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(warehouse)"
                                    >
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = warehouse"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!warehouses.data.length">
                            <td colspan="4" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No warehouses match your search.' : 'No warehouses yet.' }}
                                </p>
                                <button
                                    v-if="!search"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Add your first warehouse
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="warehouses" :links="warehouses.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h2 class="text-lg font-semibold text-brand-navy">
                    {{ editing ? 'Edit warehouse' : 'Add warehouse' }}
                </h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel value="Code" />
                    <TextInput v-model="form.code" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.code" />
                </div>
                <div>
                    <InputLabel value="Address" />
                    <TextInput v-model="form.address" class="mt-1 block w-full" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="form.is_default" />
                    Default warehouse
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox v-model:checked="form.is_active" />
                    Active
                </label>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete warehouse?"
            :item-name="deleteTarget?.name"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
