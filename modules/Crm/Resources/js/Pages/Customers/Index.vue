<script setup>
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminSortableTh from '@/Components/Admin/AdminSortableTh.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    customers: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    openCreate: { type: Boolean, default: false },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const isActive = ref(props.filters.is_active ?? '');
const groupId = ref(props.filters.customer_group_id ?? '');
const sort = ref(props.filters.sort ?? 'created_at');
const direction = ref(props.filters.direction ?? 'desc');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.customers));
const hasActiveFilters = computed(() => Boolean(search.value || isActive.value !== '' || groupId.value));
const showEmptyState = computed(() => meta.value.total === 0 && !hasActiveFilters.value);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const groupOptions = ref([...(props.groups ?? [])]);

const form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    company: '',
    address: '',
    customer_group_id: '',
    is_active: true,
    notes: '',
});

const deleteForm = useForm({});
const showQuickGroup = ref(false);
const quickForm = useForm({ name: '', is_active: true });
const quickError = ref('');

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.customer_group_id = '';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (customer) => {
    editing.value = customer;
    form.name = customer.name;
    form.code = customer.code;
    form.email = customer.email || '';
    form.phone = customer.phone || '';
    form.company = customer.company || '';
    form.address = customer.address || '';
    form.customer_group_id = customer.customer_group_id || '';
    form.is_active = customer.is_active;
    form.notes = customer.notes || '';
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    };

    if (editing.value) {
        form.put(route('crm.customers.update', editing.value.id), options);
    } else {
        form.post(route('crm.customers.store'), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('crm.customers.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const openQuickGroup = () => {
    showQuickGroup.value = true;
    quickForm.reset();
    quickForm.is_active = true;
    quickForm.clearErrors();
    quickError.value = '';
};

const submitQuickGroup = async () => {
    quickError.value = '';
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    try {
        const res = await fetch(route('commerce.pricing.customer-groups.quick'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: quickForm.name,
                is_active: true,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            quickError.value = data.errors?.name?.[0] || data.message || 'Could not create';
            return;
        }
        groupOptions.value = [...groupOptions.value, data.item];
        form.customer_group_id = data.item.id;
        showQuickGroup.value = false;
    } catch {
        quickError.value = 'Network error — try again';
    }
};

const visitIndex = () => {
    router.get(
        route('crm.customers.all'),
        {
            search: search.value || undefined,
            is_active: isActive.value !== '' ? isActive.value : undefined,
            customer_group_id: groupId.value || undefined,
            sort: sort.value,
            direction: direction.value,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const toggleSort = (column) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = column === 'created_at' ? 'desc' : 'asc';
    }
    visitIndex();
};

const clearFilters = () => {
    search.value = '';
    isActive.value = '';
    groupId.value = '';
    visitIndex();
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch([isActive, groupId], visitIndex);

onMounted(() => {
    if (props.openCreate) {
        openCreate();
    }
});
</script>

<template>
    <Head title="Customers" />

    <AdminLayout title="Customers">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <AdminEmptyState
            v-if="showEmptyState"
            title="No customers yet"
            description="Add a buyer so Sales and POS can attach orders and pricing groups."
            action-label="Add your first customer"
            @action="openCreate"
        />

        <div v-else class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">All customers</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ meta.total }} total · buyers for sales & POS</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="search" type="search" placeholder="Search name, code, email…" class="admin-data-table__search" />
                    </div>
                    <PrimaryButton type="button" @click="openCreate">Add customer</PrimaryButton>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 sm:px-5">
                <select v-model="isActive" class="admin-filter-select">
                    <option value="">All statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <select v-model="groupId" class="admin-filter-select">
                    <option value="">All groups</option>
                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                </select>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="text-xs font-medium text-brand-orange hover:text-brand-orange-dark"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <AdminSortableTh label="Name" column="name" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <AdminSortableTh label="Code" column="code" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th>Contact</th>
                            <th>Group</th>
                            <th>Status</th>
                            <AdminSortableTh label="Created" column="created_at" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in customers.data" :key="customer.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <Link :href="route('crm.customers.show', customer.id)" class="font-medium text-brand-navy hover:text-brand-orange">
                                    {{ customer.name }}
                                </Link>
                                <p v-if="customer.company" class="mt-0.5 text-xs text-gray-500">{{ customer.company }}</p>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ customer.code }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                <div>{{ customer.email || '—' }}</div>
                                <div>{{ customer.phone }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ customer.customer_group_name || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                                    :class="customer.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-gray-200'"
                                >
                                    {{ customer.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(customer.created_at) }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <Link :href="route('crm.customers.show', customer.id)" class="admin-data-table__action" title="View">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <button type="button" class="admin-data-table__action" title="Edit" @click="openEdit(customer)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button type="button" class="admin-data-table__action admin-data-table__action--danger" title="Delete" @click="deleteTarget = customer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">No customers match these filters.</td>
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
                <TablePagination :paginator="customers" :links="customers.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <form class="space-y-5 p-6" @submit.prevent="save">
                <div>
                    <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit customer' : 'Add customer' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Code auto-fills if blank. Use + to create a pricing group.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel value="Name" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Code (optional)" />
                        <TextInput
                            v-model="form.code"
                            class="mt-1 block w-full"
                            :disabled="Boolean(editing)"
                            placeholder="Auto if empty"
                        />
                        <InputError class="mt-1" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Customer group" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.customer_group_id"
                                :options="groupOptions"
                                placeholder="None"
                                search-placeholder="Search groups…"
                                creatable
                                create-label="Add group"
                                @create="openQuickGroup"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.customer_group_id" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="form.phone" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.phone" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Company" />
                        <TextInput v-model="form.company" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Address" />
                        <textarea
                            v-model="form.address"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Notes" />
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-brand-navy">
                    <Checkbox v-model:checked="form.is_active" />
                    Active
                </label>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showQuickGroup" max-width="sm" @close="showQuickGroup = false">
            <form class="space-y-4 p-6" @submit.prevent="submitQuickGroup">
                <h3 class="text-base font-semibold text-brand-navy">Add customer group</h3>
                <p class="text-xs text-gray-500">Code and default price list are set automatically.</p>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="quickForm.name" class="mt-1 block w-full" required autofocus />
                    <p v-if="quickError" class="mt-1 text-sm text-red-600">{{ quickError }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showQuickGroup = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit">Create</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete customer?"
            :item-name="deleteTarget?.name"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
