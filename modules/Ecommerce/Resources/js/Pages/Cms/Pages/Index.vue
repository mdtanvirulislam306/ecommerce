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
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    pages: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const meta = computed(() => paginationMeta(props.pages));

const emptyForm = () => ({
    title: '',
    slug: '',
    body: '',
    is_published: false,
    seo_title: '',
    seo_description: '',
});

const form = useForm(emptyForm());
const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const openEdit = (cmsPage) => {
    editing.value = cmsPage;
    form.defaults({
        title: cmsPage.title,
        slug: cmsPage.slug,
        body: cmsPage.body || '',
        is_published: cmsPage.is_published,
        seo_title: cmsPage.seo_title || '',
        seo_description: cmsPage.seo_description || '',
    });
    form.reset();
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) {
        showFormModal.value = false;
        editing.value = null;
    }
};

const submit = () => {
    if (editing.value) {
        form.put(route('ecommerce.pages.all.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                editing.value = null;
            },
        });
    } else {
        form.post(route('ecommerce.pages.all.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('ecommerce.pages.all.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('ecommerce.pages.all.index'),
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
    <Head title="All Pages" />

    <AdminLayout title="All Pages">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">CMS pages</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <Link
                        :href="route('ecommerce.pages.builder')"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-brand-navy hover:bg-gray-50"
                    >
                        Page builder
                    </Link>
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add page
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="cmsPage in pages.data" :key="cmsPage.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ cmsPage.title }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ cmsPage.slug }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="cmsPage.is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ cmsPage.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-1">
                                    <Link
                                        :href="route('ecommerce.pages.builder.edit', cmsPage.id)"
                                        class="admin-data-table__action"
                                    >
                                        Builder
                                    </Link>
                                    <button type="button" class="admin-data-table__action" @click="openEdit(cmsPage)">Edit</button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        @click="deleteTarget = cmsPage"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="pages.data.length === 0">
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">No pages yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="pages" :links="pages.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit page' : 'Add page' }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Title" />
                        <TextInput v-model="form.title" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.title" />
                    </div>
                    <div>
                        <InputLabel value="Slug (optional)" />
                        <TextInput v-model="form.slug" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.slug" />
                    </div>
                    <label class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_published" />
                        <span class="text-sm">Published</span>
                    </label>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeFormModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete page?"
            :item-name="deleteTarget?.title"
            confirm-label="Delete"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
