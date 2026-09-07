<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
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
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const editingBrand = ref(null);
const deleteTarget = ref(null);

const emptyForm = () => ({
    name: '',
    slug: '',
    is_active: true,
    sort_order: 0,
});

const form = useForm(emptyForm());
const deleteForm = useForm({});

const meta = computed(() => paginationMeta(props.brands));

const openCreate = () => {
    editingBrand.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const openEdit = (brand) => {
    editingBrand.value = brand;
    form.defaults({
        name: brand.name,
        slug: brand.slug,
        is_active: brand.is_active,
        sort_order: brand.sort_order,
    });
    form.reset();
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) {
        showFormModal.value = false;
        editingBrand.value = null;
    }
};

const submit = () => {
    if (editingBrand.value) {
        form.put(route('products.brands.update', editingBrand.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                editingBrand.value = null;
            },
        });
    } else {
        form.post(route('products.brands.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('products.brands.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('products.brands.index'),
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
</script>

<template>
    <Head title="Brands" />

    <AdminLayout title="Brands">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Brands</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search brands…"
                        class="admin-data-table__search"
                    />
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add brand
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="brand in brands.data" :key="brand.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ brand.name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ brand.slug }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="brand.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ brand.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ brand.sort_order }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(brand)"
                                    >
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = brand"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="brands.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No brands match your search.' : 'No brands yet.' }}
                                </p>
                                <button
                                    v-if="!search"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Add your first brand
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select
                    v-model.number="perPage"
                    class="admin-filter-select text-xs"
                    @change="visitIndex"
                >
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="brands" :links="brands.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">
                    {{ editingBrand ? 'Edit brand' : 'Add brand' }}
                </h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="slug" value="Slug (optional)" />
                        <TextInput id="slug" v-model="form.slug" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.slug" />
                    </div>
                    <div>
                        <InputLabel for="sort_order" value="Sort order" />
                        <TextInput id="sort_order" v-model="form.sort_order" type="number" class="mt-1 block w-full" />
                    </div>
                    <label class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <div class="flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" @click="closeFormModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete brand?"
            message="Products linked to this brand will lose the brand reference."
            :item-name="deleteTarget?.name"
            confirm-label="Delete brand"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
