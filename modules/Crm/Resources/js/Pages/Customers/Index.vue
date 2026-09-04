<script setup>
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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
const perPage = ref(props.filters.per_page ?? 25);
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
        { search: search.value || undefined, per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});

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

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">All customers</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Buyers for sales & POS — assign a group for pricing.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <PrimaryButton type="button" @click="openCreate">Add customer</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Code</th>
                            <th>Contact</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in customers.data" :key="customer.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">
                                {{ customer.name }}
                                <div v-if="customer.company" class="text-xs font-normal text-gray-500">{{ customer.company }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ customer.code }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                <div>{{ customer.email || '—' }}</div>
                                <div>{{ customer.phone }}</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ customer.customer_group_name || '—' }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="customer.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ customer.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(customer.created_at) }}</td>
                            <td class="admin-data-table__cell text-right">
                                <button type="button" class="admin-data-table__action" @click="openEdit(customer)">Edit</button>
                                <button type="button" class="admin-data-table__action text-red-600" @click="deleteTarget = customer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">No customers yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
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
