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
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    referrals: { type: Object, required: true },
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
    code: '',
    referrer_name: '',
    referrer_email: '',
    referee_email: '',
    status: 'pending',
    reward_amount: 0,
});

const form = useForm(emptyForm());
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.referrals));

const openCreate = () => {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    showFormModal.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    form.defaults({
        code: item.code,
        referrer_name: item.referrer_name,
        referrer_email: item.referrer_email || '',
        referee_email: item.referee_email || '',
        status: item.status,
        reward_amount: item.reward_amount,
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
        form.put(route('commerce.referrals.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                editing.value = null;
            },
        });
    } else {
        form.post(route('commerce.referrals.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('commerce.referrals.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

let searchTimer = null;
const visitIndex = () => {
    router.get(
        route('commerce.referrals.index'),
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
    <Head title="Referrals" />

    <AdminLayout title="Referrals">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Referrals</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} total</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange-dark"
                        @click="openCreate"
                    >
                        Add referral
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Code</th>
                            <th>Referrer</th>
                            <th>Referee</th>
                            <th>Status</th>
                            <th>Reward</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in referrals.data" :key="item.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ item.code }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.referrer_name }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.referee_email || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.status }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ item.reward_amount }}</td>
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
                        <tr v-if="referrals.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No referrals yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="referrals" :links="referrals.links" />
            </div>
        </div>

        <Modal :show="showFormModal" @close="closeFormModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit referral' : 'Add referral' }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Code" />
                        <TextInput v-model="form.code" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Referrer name" />
                        <TextInput v-model="form.referrer_name" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel value="Referrer email" />
                        <TextInput v-model="form.referrer_email" type="email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Referee email" />
                        <TextInput v-model="form.referee_email" type="email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Reward amount" />
                        <TextInput v-model="form.reward_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
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
            title="Delete referral?"
            :item-name="deleteTarget?.code"
            confirm-label="Delete"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
