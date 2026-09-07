<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import MediaPicker from '@/Components/Admin/MediaPicker.vue';
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
    categories: { type: Object, required: true },
    parentOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const showMediaPicker = ref(false);
const editingCategory = ref(null);
const deleteTarget = ref(null);
const imagePreview = ref(null);

const emptyForm = () => ({
    parent_id: '',
    name: '',
    slug: '',
    description: '',
    media_library_id: null,
    clear_image: false,
    is_active: true,
    sort_order: 0,
});

const form = useForm(emptyForm());
const deleteForm = useForm({});

const meta = computed(() => paginationMeta(props.categories));

const parentLabel = (parentId) => {
    if (!parentId) return '—';
    const parent = props.parentOptions.find((c) => c.id === parentId);
    return parent?.name ?? '—';
};

const categoryImageUrl = (category) => category?.image_url || null;

const openCreate = () => {
    editingCategory.value = null;
    form.defaults(emptyForm());
    form.reset();
    form.clear_image = false;
    imagePreview.value = null;
    showFormModal.value = true;
};

const openEdit = (category) => {
    editingCategory.value = category;
    form.defaults({
        parent_id: category.parent_id || '',
        name: category.name,
        slug: category.slug,
        description: category.description || '',
        media_library_id: category.media_library_id || null,
        clear_image: false,
        is_active: category.is_active,
        sort_order: category.sort_order,
    });
    form.reset();
    imagePreview.value = categoryImageUrl(category);
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) {
        showFormModal.value = false;
        editingCategory.value = null;
    }
};

const onMediaSelect = (items) => {
    const selected = Array.isArray(items) ? items[0] : items;
    if (!selected) {
        return;
    }

    form.media_library_id = selected.id;
    form.clear_image = false;
    imagePreview.value = selected.url || null;
    showMediaPicker.value = false;
};

const clearImage = () => {
    form.media_library_id = null;
    form.clear_image = true;
    imagePreview.value = null;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showFormModal.value = false;
            editingCategory.value = null;
            imagePreview.value = null;
        },
    };

    if (editingCategory.value) {
        form.put(route('products.categories.update', editingCategory.value.id), options);
        return;
    }

    form.post(route('products.categories.store'), options);
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('products.categories.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('products.categories.index'),
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
    <Head title="Categories" />

    <AdminLayout title="Categories">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Categories</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search categories…"
                        class="admin-data-table__search"
                    />
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add category
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Image</th>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="category in categories.data" :key="category.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <img
                                    v-if="categoryImageUrl(category)"
                                    :src="categoryImageUrl(category)"
                                    :alt="category.name"
                                    class="h-10 w-10 rounded-lg object-cover"
                                />
                                <span v-else class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-[10px] text-gray-400">—</span>
                            </td>
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ category.name }}</td>
                            <td class="admin-data-table__cell text-gray-600">
                                {{ category.parent?.name || parentLabel(category.parent_id) }}
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ category.slug }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(category)"
                                    >
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = category"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{ search ? 'No categories match your search.' : 'No categories yet.' }}
                                </p>
                                <button
                                    v-if="!search"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Add your first category
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
                <TablePagination :paginator="categories" :links="categories.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">
                    {{ editingCategory ? 'Edit category' : 'Add category' }}
                </h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="parent_id" value="Parent category" />
                        <select
                            id="parent_id"
                            v-model="form.parent_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        >
                            <option value="">None (root)</option>
                            <option
                                v-for="parent in parentOptions"
                                :key="parent.id"
                                :value="parent.id"
                                :disabled="editingCategory && parent.id === editingCategory.id"
                            >
                                {{ parent.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="slug" value="Slug (optional)" />
                        <TextInput id="slug" v-model="form.slug" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        />
                    </div>
                    <div>
                        <InputLabel value="Image" />
                        <div class="mt-1 flex items-start gap-3">
                            <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-xl bg-gray-50 ring-1 ring-gray-100">
                                <img v-if="imagePreview" :src="imagePreview" alt="" class="h-full w-full object-cover" />
                                <span v-else class="text-[10px] text-gray-400">No image</span>
                            </div>
                            <div class="space-y-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-brand-teal/10 px-3 py-1.5 text-sm font-medium text-brand-teal-dark hover:bg-brand-teal/20"
                                    @click="showMediaPicker = true"
                                >
                                    Choose from media library
                                </button>
                                <button
                                    v-if="imagePreview"
                                    type="button"
                                    class="block text-xs font-medium text-red-600 hover:underline"
                                    @click="clearImage"
                                >
                                    Remove image
                                </button>
                                <p class="text-[11px] text-gray-400">Upload new files from Media Library if needed.</p>
                            </div>
                        </div>
                        <InputError class="mt-1" :message="form.errors.media_library_id" />
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

        <MediaPicker
            :show="showMediaPicker"
            :multiple="false"
            accept="image/*"
            title="Select category image"
            :selected-ids="form.media_library_id ? [form.media_library_id] : []"
            @close="showMediaPicker = false"
            @select="onMediaSelect"
        />

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete category?"
            message="Child categories and product links may be affected."
            :item-name="deleteTarget?.name"
            confirm-label="Delete category"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
