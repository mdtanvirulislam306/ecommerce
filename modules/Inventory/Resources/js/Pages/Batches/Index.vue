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
    batches: { type: Object, required: true },
    warehouses: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const deleteForm = useForm({});

const form = useForm({
    batch_number: '',
    product_id: '',
    warehouse_id: '',
    quantity: 0,
    manufactured_at: '',
    expires_at: '',
    notes: '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.batch_number = row.batch_number;
    form.product_id = row.product_id;
    form.warehouse_id = row.warehouse_id;
    form.quantity = row.quantity;
    form.manufactured_at = row.manufactured_at || '';
    form.expires_at = row.expires_at || '';
    form.notes = row.notes || '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('inventory.batches.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('inventory.batches.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route('inventory.batches.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Batches" />
    <AdminLayout title="Batches">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
        <div class="mb-4 flex justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search batches…" />
            <PrimaryButton type="button" @click="openCreate">Add batch</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Batch</th>
                        <th class="pb-2">Product</th>
                        <th class="pb-2">Warehouse</th>
                        <th class="pb-2">Qty</th>
                        <th class="pb-2">Expires</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in batches.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium">{{ row.batch_number }}</td>
                        <td class="py-2">{{ row.product_name }}</td>
                        <td class="py-2">{{ row.warehouse_name }}</td>
                        <td class="py-2">{{ row.quantity }}</td>
                        <td class="py-2">{{ row.expires_at || '—' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="batches" class="mt-4" />
        </section>
        <Modal :show="showModal" @close="showModal = false">
            <div class="space-y-3 p-6">
                <h2 class="text-lg font-semibold">{{ editing ? 'Edit batch' : 'Add batch' }}</h2>
                <div>
                    <InputLabel value="Batch number" />
                    <TextInput v-model="form.batch_number" class="mt-1 block w-full" />
                    <InputError :message="form.errors.batch_number" />
                </div>
                <div>
                    <InputLabel value="Product" />
                    <select v-model="form.product_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select…</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Warehouse" />
                    <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select…</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Quantity" />
                    <TextInput v-model="form.quantity" type="number" min="0" step="any" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete batch?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('inventory.batches.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
