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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    promotions: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', per_page: 25, type: null }) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
    pageTitle: { type: String, default: 'All promotions' },
    fixedType: { type: String, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showFormModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const typeOptions = [
    { value: 'percentage', label: 'Percentage' },
    { value: 'fixed', label: 'Fixed amount' },
    { value: 'discount_rule', label: 'Discount rule' },
    { value: 'buy_x_get_y', label: 'Buy X get Y' },
    { value: 'free_shipping', label: 'Free shipping' },
];

const indexRoute = computed(() => {
    if (props.fixedType === 'discount_rule') return 'commerce.promotions.discount-rules';
    if (props.fixedType === 'buy_x_get_y') return 'commerce.promotions.buy-x-get-y';
    if (props.fixedType === 'free_shipping') return 'commerce.promotions.free-shipping';
    return 'commerce.promotions.index';
});

const emptyForm = () => ({
    name: '',
    type: props.fixedType || 'percentage',
    value: 0,
    buy_qty: '',
    get_qty: '',
    starts_at: '',
    ends_at: '',
    is_active: true,
    description: '',
});

const form = useForm(emptyForm());
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.promotions));

const openCreate = () => {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    form.defaults({
        name: item.name,
        type: item.type,
        value: item.value,
        buy_qty: item.buy_qty ?? '',
        get_qty: item.get_qty ?? '',
        starts_at: item.starts_at ? item.starts_at.slice(0, 16) : '',
        ends_at: item.ends_at ? item.ends_at.slice(0, 16) : '',
        is_active: item.is_active,
        description: item.description || '',
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
        form.put(route('commerce.promotions.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                editing.value = null;
            },
        });
    } else {
        form.post(route('commerce.promotions.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('commerce.promotions.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route(indexRoute.value),
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
    <Head :title="pageTitle" />

    <AdminLayout :title="pageTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">{{ pageTitle }}</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add promotion
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in promotions.data" :key="item.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ item.name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.type }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.value }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-1">
                                    <button type="button" class="admin-data-table__action" @click="openEdit(item)">Edit</button>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        @click="deleteTarget = item"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="promotions.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">No promotions yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="promotions" :links="promotions.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">
                    {{ editing ? 'Edit promotion' : 'Add promotion' }}
                </h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div v-if="!fixedType">
                        <InputLabel value="Type" />
                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.type" />
                    </div>
                    <div>
                        <InputLabel value="Value" />
                        <TextInput v-model="form.value" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.value" />
                    </div>
                    <div v-if="form.type === 'buy_x_get_y'" class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Buy qty" />
                            <TextInput v-model="form.buy_qty" type="number" min="1" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Get qty" />
                            <TextInput v-model="form.get_qty" type="number" min="1" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Starts at" />
                            <TextInput v-model="form.starts_at" type="datetime-local" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Ends at" />
                            <TextInput v-model="form.ends_at" type="datetime-local" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Description" />
                        <textarea v-model="form.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <label class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="text-sm">Active</span>
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
            title="Delete promotion?"
            :item-name="deleteTarget?.name"
            confirm-label="Delete"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
