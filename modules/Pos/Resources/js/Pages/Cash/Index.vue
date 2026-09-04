<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    movements: { type: Object, required: true },
    sessions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const showModal = ref(false);
const perPage = ref(props.filters.per_page ?? 25);
const form = useForm({
    pos_session_id: props.filters.pos_session_id || '',
    type: 'in',
    amount: '',
    reason: '',
    notes: '',
});

const save = () => {
    form.post(route('pos.cash-management.store'), {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; form.reset('amount', 'reason', 'notes'); form.type = 'in'; },
    });
};
</script>

<template>
    <Head title="Cash Management" />
    <AdminLayout title="Cash Management">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Cash in / out</h2>
                <PrimaryButton type="button" @click="showModal = true">Record movement</PrimaryButton>
            </div>
            <table class="min-w-full">
                <thead><tr><th>Session</th><th>Type</th><th>Amount</th><th>Reason</th><th>When</th></tr></thead>
                <tbody>
                    <tr v-for="row in movements.data" :key="row.id">
                        <td>{{ row.session_label }}</td>
                        <td class="capitalize">{{ row.type }}</td>
                        <td>{{ Number(row.amount).toFixed(2) }}</td>
                        <td>{{ row.reason || '—' }}</td>
                        <td class="text-sm text-gray-500">{{ row.created_at ? new Date(row.created_at).toLocaleString() : '—' }}</td>
                    </tr>
                    <tr v-if="!movements.data.length"><td colspan="5" class="py-10 text-center text-gray-500">No cash movements yet.</td></tr>
                </tbody>
            </table>
            <TablePagination :paginator="movements" :per-page="perPage" :per-page-options="perPageOptions" @change-page="(p) => router.get(route('pos.cash-management'), { per_page: perPage, page: p }, { preserveState: true, replace: true })" @change-per-page="(v) => { perPage = v; router.get(route('pos.cash-management'), { per_page: v }, { preserveState: true, replace: true }); }" />
        </div>
        <Modal :show="showModal" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h3 class="text-lg font-semibold text-brand-navy">Cash movement</h3>
                <div>
                    <InputLabel value="Open session" />
                    <select v-model="form.pos_session_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select…</option>
                        <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.label }}</option>
                    </select>
                    <InputError :message="form.errors.pos_session_id" />
                </div>
                <div>
                    <InputLabel value="Type" />
                    <select v-model="form.type" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="in">Cash in</option>
                        <option value="out">Cash out</option>
                    </select>
                </div>
                <div><InputLabel value="Amount" /><TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" /><InputError :message="form.errors.amount" /></div>
                <div><InputLabel value="Reason" /><TextInput v-model="form.reason" class="mt-1 block w-full" /></div>
                <div class="flex justify-end gap-2"><SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing">Save</PrimaryButton></div>
            </form>
        </Modal>
    </AdminLayout>
</template>
