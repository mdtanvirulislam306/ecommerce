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
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    serials: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.serials));

const statusMeta = {
    in_stock: 'bg-emerald-50 text-emerald-700',
    sold: 'bg-sky-50 text-sky-700',
    damaged: 'bg-red-50 text-red-700',
    returned: 'bg-amber-50 text-amber-800',
};

const form = useForm({
    serial_number: '',
    product_id: '',
    warehouse_id: '',
    status: 'in_stock',
    notes: '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.status = 'in_stock';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.serial_number = row.serial_number;
    form.product_id = row.product_id;
    form.warehouse_id = row.warehouse_id || '';
    form.status = row.status;
    form.notes = row.notes || '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('inventory.serial-numbers.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('inventory.serial-numbers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const visitIndex = () => {
    router.get(
        route('inventory.serial-numbers.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch(status, visitIndex);
</script>

<template>
    <Head title="Serial numbers" />

    <AdminLayout title="Serial numbers">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Serial numbers</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} units tracked</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search serials…" class="admin-data-table__search" />
                    <select v-model="status" class="admin-filter-select text-xs">
                        <option value="">All statuses</option>
                        <option value="in_stock">In stock</option>
                        <option value="sold">Sold</option>
                        <option value="damaged">Damaged</option>
                        <option value="returned">Returned</option>
                    </select>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add serial
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Serial</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in serials.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-mono text-sm font-medium text-brand-navy">
                                {{ row.serial_number }}
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.product_name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.warehouse_name || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    :class="statusMeta[row.status] || 'bg-gray-100 text-gray-600'"
                                >
                                    {{ row.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(row)"
                                    >
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = row"
                                    >
                                        <ActionIcon name="delete" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!serials.data.length">
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-500">
                                    {{
                                        search || status
                                            ? 'No serials match your filters.'
                                            : 'No serial numbers yet.'
                                    }}
                                </p>
                                <button
                                    v-if="!search && !status"
                                    type="button"
                                    class="mt-3 text-sm font-medium text-brand-orange hover:underline"
                                    @click="openCreate"
                                >
                                    Add your first serial
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
                <TablePagination :paginator="serials" :links="serials.links" />
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit serial' : 'Add serial' }}</h2>
                <div>
                    <InputLabel value="Serial number" />
                    <TextInput v-model="form.serial_number" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.serial_number" />
                </div>
                <div>
                    <InputLabel value="Product" />
                    <select v-model="form.product_id" class="admin-filter-select mt-1 block w-full" required>
                        <option value="">Select…</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.product_id" />
                </div>
                <div>
                    <InputLabel value="Warehouse" />
                    <select v-model="form.warehouse_id" class="admin-filter-select mt-1 block w-full">
                        <option value="">None</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="form.status" class="admin-filter-select mt-1 block w-full">
                        <option value="in_stock">In stock</option>
                        <option value="sold">Sold</option>
                        <option value="damaged">Damaged</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Notes" />
                    <TextInput v-model="form.notes" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete serial?"
            :item-name="deleteTarget?.serial_number"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="
                deleteForm.delete(route('inventory.serial-numbers.destroy', deleteTarget.id), {
                    onSuccess: () => (deleteTarget = null),
                })
            "
        />
    </AdminLayout>
</template>
