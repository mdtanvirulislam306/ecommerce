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
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    referrals: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    code: '',
    referrer_name: '',
    referrer_email: '',
    referee_email: '',
    status: 'pending',
    reward_amount: 0,
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.status = 'pending';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.code = row.code;
    form.referrer_name = row.referrer_name;
    form.referrer_email = row.referrer_email;
    form.referee_email = row.referee_email || '';
    form.status = row.status;
    form.reward_amount = row.reward_amount;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('marketing.referrals.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('marketing.referrals.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route('marketing.referrals.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Referrals" />

    <AdminLayout title="Referral Program">
        <p class="mb-4 text-sm text-gray-500">Track referral codes, referrers, and reward payouts.</p>

        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search referrals…" />
            <PrimaryButton type="button" @click="openCreate">New referral</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Code</th>
                        <th class="pb-2">Referrer</th>
                        <th class="pb-2">Referee</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2">Reward</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in referrals.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-mono text-brand-navy">{{ row.code }}</td>
                        <td class="py-2">
                            <p class="font-medium">{{ row.referrer_name }}</p>
                            <p class="text-xs text-gray-500">{{ row.referrer_email }}</p>
                        </td>
                        <td class="py-2 text-gray-500">{{ row.referee_email || '—' }}</td>
                        <td class="py-2">{{ row.status_label }}</td>
                        <td class="py-2">{{ row.reward_amount }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="referrals" class="mt-4" />
        </section>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit referral' : 'New referral' }}</h2>
                <div>
                    <InputLabel value="Code (auto-generated if empty)" />
                    <TextInput v-model="form.code" class="mt-1 block w-full font-mono uppercase" />
                    <InputError :message="form.errors.code" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Referrer name" />
                        <TextInput v-model="form.referrer_name" class="mt-1 block w-full" />
                        <InputError :message="form.errors.referrer_name" />
                    </div>
                    <div>
                        <InputLabel value="Referrer email" />
                        <TextInput v-model="form.referrer_email" type="email" class="mt-1 block w-full" />
                        <InputError :message="form.errors.referrer_email" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Referee email" />
                        <TextInput v-model="form.referee_email" type="email" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Reward amount" />
                        <TextInput v-model="form.reward_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                    </div>
                </div>
                <div v-if="editing">
                    <InputLabel value="Status" />
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete referral?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('marketing.referrals.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
