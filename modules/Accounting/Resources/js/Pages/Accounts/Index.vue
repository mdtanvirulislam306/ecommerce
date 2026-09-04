<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    listTitle: { type: String, default: 'Chart of Accounts' },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    code: '',
    name: '',
    type: props.filters.type || 'asset',
    parent_id: '',
    is_active: true,
    sort_order: 0,
    description: '',
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.type = props.filters.type || 'asset';
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (account) => {
    editing.value = account;
    form.code = account.code;
    form.name = account.name;
    form.type = account.type;
    form.parent_id = account.parent_id || '';
    form.is_active = account.is_active;
    form.sort_order = account.sort_order;
    form.description = account.description || '';
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
        form.put(route('accounting.accounts.update', editing.value.id), options);
    } else {
        form.post(route('accounting.accounts.store'), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('accounting.accounts.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const typeMeta = {
    asset: 'bg-sky-50 text-sky-700',
    liability: 'bg-amber-50 text-amber-800',
    equity: 'bg-violet-50 text-violet-700',
    income: 'bg-emerald-50 text-emerald-700',
    expense: 'bg-orange-50 text-brand-orange',
};
</script>

<template>
    <Head :title="listTitle" />

    <AdminLayout :title="listTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">{{ listTitle }}</h2>
                <PrimaryButton type="button" @click="openCreate">Add account</PrimaryButton>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>System</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="account in accounts" :key="account.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-mono text-sm text-brand-navy">{{ account.code }}</td>
                            <td class="admin-data-table__cell font-medium text-brand-navy">
                                {{ account.name }}
                                <div v-if="account.description" class="text-xs font-normal text-gray-500">{{ account.description }}</div>
                            </td>
                            <td class="admin-data-table__cell">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="typeMeta[account.type]">
                                    {{ account.type_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-500">{{ account.is_system ? 'Yes' : '—' }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="account.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ account.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-right">
                                <button type="button" class="admin-data-table__action" @click="openEdit(account)">Edit</button>
                                <button
                                    v-if="!account.is_system"
                                    type="button"
                                    class="admin-data-table__action text-red-600"
                                    @click="deleteTarget = account"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!accounts.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No accounts yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit account' : 'Add account' }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Code" />
                        <TextInput v-model="form.code" class="mt-1 block w-full" required :disabled="editing?.is_system" />
                        <InputError class="mt-1" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Type" />
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            :disabled="editing?.is_system"
                            required
                        >
                            <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Name" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Description" />
                        <TextInput v-model="form.description" class="mt-1 block w-full" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete account?"
            :item-name="deleteTarget?.name"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
