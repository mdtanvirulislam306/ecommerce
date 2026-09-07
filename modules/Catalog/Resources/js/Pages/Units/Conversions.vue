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
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    conversions: { type: Object, required: true },
    units: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ per_page: 25 }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const deleteTarget = ref(null);

const form = useForm({
    from_unit_id: '',
    to_unit_id: '',
    factor: '',
});

const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.conversions));

const openCreate = () => {
    form.reset();
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) showFormModal.value = false;
};

const submit = () => {
    form.post(route('products.units.conversions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showFormModal.value = false;
            form.reset();
        },
    });
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('products.units.conversions.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const visitIndex = () => {
    router.get(route('products.units.conversions.index'), { per_page: perPage.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Unit Conversions" />

    <AdminLayout title="Unit Conversions">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Unit conversions</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('products.units.index')"
                        class="text-sm font-medium text-brand-navy hover:text-brand-orange"
                    >
                        ← Units
                    </Link>
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add conversion
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>From</th>
                            <th>To</th>
                            <th>Factor</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in conversions.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell text-brand-navy">
                                {{ row.from_unit?.name }} ({{ row.from_unit?.code }})
                            </td>
                            <td class="admin-data-table__cell text-brand-navy">
                                {{ row.to_unit?.name }} ({{ row.to_unit?.code }})
                            </td>
                            <td class="admin-data-table__cell font-medium text-gray-700">{{ row.factor }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
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
                        <tr v-if="conversions.data.length === 0">
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">
                                No conversions yet. Example: 1 Carton = 12 Pieces.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="admin-filter-select text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="conversions" :links="conversions.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">Add conversion</h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel value="From unit" />
                        <select
                            v-model="form.from_unit_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            required
                        >
                            <option value="">Select…</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }} ({{ unit.code }})
                            </option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.from_unit_id" />
                    </div>
                    <div>
                        <InputLabel value="To unit" />
                        <select
                            v-model="form.to_unit_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            required
                        >
                            <option value="">Select…</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }} ({{ unit.code }})
                            </option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.to_unit_id" />
                    </div>
                    <div>
                        <InputLabel value="Factor (1 from = factor × to)" />
                        <TextInput v-model="form.factor" type="number" step="0.000001" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.factor" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeFormModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete conversion?"
            confirm-label="Delete"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
