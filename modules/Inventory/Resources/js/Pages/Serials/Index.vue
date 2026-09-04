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
    serials: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const deleteForm = useForm({});

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
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('inventory.serial-numbers.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch([search, status], () => {
    router.get(
        route('inventory.serial-numbers.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Serial numbers" />
    <AdminLayout title="Serial numbers">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap justify-between gap-3">
            <div class="flex gap-2">
                <TextInput v-model="search" type="search" class="w-56" placeholder="Search serials…" />
                <select v-model="status" class="rounded-md border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    <option value="in_stock">In stock</option>
                    <option value="sold">Sold</option>
                    <option value="damaged">Damaged</option>
                    <option value="returned">Returned</option>
                </select>
            </div>
            <PrimaryButton type="button" @click="openCreate">Add serial</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Serial</th>
                        <th class="pb-2">Product</th>
                        <th class="pb-2">Warehouse</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in serials.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium">{{ row.serial_number }}</td>
                        <td class="py-2">{{ row.product_name }}</td>
                        <td class="py-2">{{ row.warehouse_name || '—' }}</td>
                        <td class="py-2 capitalize">{{ row.status.replace('_', ' ') }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="serials" class="mt-4" />
        </section>
        <Modal :show="showModal" @close="showModal = false">
            <div class="space-y-3 p-6">
                <h2 class="text-lg font-semibold">{{ editing ? 'Edit serial' : 'Add serial' }}</h2>
                <div>
                    <InputLabel value="Serial number" />
                    <TextInput v-model="form.serial_number" class="mt-1 block w-full" />
                    <InputError :message="form.errors.serial_number" />
                </div>
                <div>
                    <InputLabel value="Product" />
                    <select v-model="form.product_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select…</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="in_stock">In stock</option>
                        <option value="sold">Sold</option>
                        <option value="damaged">Damaged</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete serial?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('inventory.serial-numbers.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
