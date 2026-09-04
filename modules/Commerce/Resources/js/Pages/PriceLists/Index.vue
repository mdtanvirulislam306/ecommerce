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
    priceLists: { type: Object, required: true },
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

const emptyForm = () => ({
    name: '',
    code: '',
    description: '',
    currency: 'BDT',
    is_active: true,
    is_default: false,
    sort_order: 0,
});

const form = useForm(emptyForm());
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.priceLists));

const openCreate = () => {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const closeFormModal = () => {
    if (!form.processing) showFormModal.value = false;
};

const submit = () => {
    form.post(route('commerce.pricing.price-lists.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showFormModal.value = false;
        },
    });
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('commerce.pricing.price-lists.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('commerce.pricing.price-lists.index'),
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
    <Head title="Price Lists" />

    <AdminLayout title="Price Lists">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Price lists</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total · Retail, wholesale, dealer, VIP…</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        New price list
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Code</th>
                            <th>Currency</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="list in priceLists.data" :key="list.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <Link
                                    :href="route('commerce.pricing.price-lists.show', list.id)"
                                    class="font-medium text-brand-navy hover:text-brand-orange"
                                >
                                    {{ list.name }}
                                    <span v-if="list.is_default" class="ml-1 text-xs text-brand-teal">(default)</span>
                                </Link>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ list.code }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ list.currency }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ list.items_count }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="list.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ list.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex justify-end gap-1">
                                    <Link
                                        :href="route('commerce.pricing.price-lists.show', list.id)"
                                        class="admin-data-table__action"
                                    >
                                        Manage
                                    </Link>
                                    <button
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        @click="deleteTarget = list"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="priceLists.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No price lists yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="priceLists" :links="priceLists.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">New price list</h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Code" />
                        <TextInput v-model="form.code" class="mt-1 block w-full" required placeholder="retail" />
                        <InputError class="mt-1" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Currency" />
                        <TextInput v-model="form.currency" class="mt-1 block w-full" maxlength="3" />
                    </div>
                    <label class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_default" />
                        <span class="text-sm">Default price list</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="text-sm">Active</span>
                    </label>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeFormModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Create</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete price list?"
            :item-name="deleteTarget?.name"
            confirm-label="Delete"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
