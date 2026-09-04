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
    segments: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
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
const form = useForm({ name: '', code: '', description: '', is_active: true, sort_order: 0, customer_ids: [] });
const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.customer_ids = [];
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (segment) => {
    editing.value = segment;
    form.name = segment.name;
    form.code = segment.code;
    form.description = segment.description || '';
    form.is_active = segment.is_active;
    form.sort_order = segment.sort_order;
    form.customer_ids = segment.customer_ids || [];
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => { showModal.value = false; } };
    if (editing.value) {
        form.put(route('crm.customers.segments.update', editing.value.id), options);
    } else {
        form.post(route('crm.customers.segments.store'), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('crm.customers.segments.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null; },
    });
};

const visitIndex = () => {
    router.get(route('crm.customers.segments'), { search: search.value || undefined, per_page: perPage.value }, { preserveState: true, replace: true });
};

let searchTimer = null;
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(visitIndex, 300); });
</script>

<template>
    <Head title="Customer Segments" />
    <AdminLayout title="Customer Segments">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Segments</h2>
                <div class="flex gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <PrimaryButton type="button" @click="openCreate">Add segment</PrimaryButton>
                </div>
            </div>
            <table class="min-w-full">
                <thead><tr><th>Name</th><th>Code</th><th>Customers</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="segment in segments.data" :key="segment.id">
                        <td class="font-medium text-brand-navy">{{ segment.name }}</td>
                        <td class="font-mono text-xs">{{ segment.code }}</td>
                        <td>{{ segment.customers_count }}</td>
                        <td>{{ segment.is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="space-x-2 text-right">
                            <button type="button" class="text-sm text-brand-navy" @click="openEdit(segment)">Edit</button>
                            <button type="button" class="text-sm text-red-600" @click="deleteTarget = segment">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!segments.data.length"><td colspan="5" class="py-10 text-center text-gray-500">No segments yet.</td></tr>
                </tbody>
            </table>
            <TablePagination :paginator="segments" :per-page="perPage" :per-page-options="perPageOptions" @change-page="(p) => router.get(route('crm.customers.segments'), { search: search || undefined, per_page: perPage, page: p }, { preserveState: true, replace: true })" @change-per-page="(v) => { perPage = v; visitIndex(); }" />
        </div>
        <Modal :show="showModal" max-width="lg" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit segment' : 'Add segment' }}</h3>
                <div><InputLabel value="Name" /><TextInput v-model="form.name" class="mt-1 block w-full" /><InputError :message="form.errors.name" /></div>
                <div><InputLabel value="Code" /><TextInput v-model="form.code" class="mt-1 block w-full" /><InputError :message="form.errors.code" /></div>
                <div><InputLabel value="Description" /><TextInput v-model="form.description" class="mt-1 block w-full" /></div>
                <div>
                    <InputLabel value="Customers" />
                    <select v-model="form.customer_ids" multiple class="mt-1 h-40 w-full rounded-md border-gray-300 text-sm">
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }} ({{ customer.code }})</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
                <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div>
            </form>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="confirmDelete" />
    </AdminLayout>
</template>
