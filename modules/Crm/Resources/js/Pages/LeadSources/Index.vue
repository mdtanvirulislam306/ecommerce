<script setup>
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminSortableTh from '@/Components/Admin/AdminSortableTh.vue';
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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    sources: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const isActive = ref(props.filters.is_active ?? '');
const sort = ref(props.filters.sort ?? 'sort_order');
const direction = ref(props.filters.direction ?? 'asc');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const form = useForm({ name: '', code: '', is_active: true, sort_order: 0 });
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.sources));
const hasActiveFilters = computed(() => Boolean(search.value || isActive.value !== ''));
const showEmptyState = computed(() => meta.value.total === 0 && !hasActiveFilters.value);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (source) => {
    editing.value = source;
    form.name = source.name;
    form.code = source.code;
    form.is_active = source.is_active;
    form.sort_order = source.sort_order;
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => { showModal.value = false; } };
    if (editing.value) {
        form.put(route('crm.leads.sources.update', editing.value.id), options);
    } else {
        form.post(route('crm.leads.sources.store'), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }
    deleteForm.delete(route('crm.leads.sources.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
};

const visitIndex = () => {
    router.get(route('crm.leads.sources'), {
        search: search.value || undefined,
        is_active: isActive.value !== '' ? isActive.value : undefined,
        sort: sort.value,
        direction: direction.value,
        per_page: perPage.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const toggleSort = (column) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }
    visitIndex();
};

let searchTimer = null;
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(visitIndex, 300); });
watch(isActive, visitIndex);
</script>

<template>
    <Head title="Lead Sources" />
    <AdminLayout title="Lead Sources">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>

        <AdminEmptyState
            v-if="showEmptyState"
            title="No lead sources yet"
            description="Track where prospects come from — website, referral, walk-in, ads."
            action-label="Add source"
            @action="openCreate"
        />

        <div v-else class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Lead sources</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    </div>
                    <select v-model="isActive" class="admin-filter-select">
                        <option value="">All statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <PrimaryButton type="button" @click="openCreate">Add source</PrimaryButton>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <AdminSortableTh label="Name" column="name" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <AdminSortableTh label="Code" column="code" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <AdminSortableTh label="Leads" column="leads_count" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="source in sources.data" :key="source.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ source.name }}</td>
                            <td class="admin-data-table__cell font-mono text-xs text-gray-500">{{ source.code }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ source.leads_count }}</td>
                            <td class="admin-data-table__cell">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1" :class="source.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-gray-200'">
                                    {{ source.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-0.5">
                                    <button type="button" class="admin-data-table__action" title="Edit" @click="openEdit(source)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button type="button" class="admin-data-table__action admin-data-table__action--danger" title="Delete" @click="deleteTarget = source">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!sources.data.length">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">No sources match these filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="admin-data-table__footer">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <span>Rows per page</span>
                        <select v-model.number="perPage" class="admin-filter-select py-1.5" @change="visitIndex">
                            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </label>
                    <span>Showing {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} of {{ meta.total }}</span>
                </div>
                <TablePagination :paginator="sources" :links="sources.links" />
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit source' : 'Add source' }}</h3>
                <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /><InputError :message="form.errors.name" /></div>
                <div><InputLabel value="Code" /><TextInput v-model="form.code" class="mt-1 block w-full" /><InputError :message="form.errors.code" /></div>
                <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
                <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div>
            </form>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" :item-name="deleteTarget?.name" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="confirmDelete" />
    </AdminLayout>
</template>
