<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import AdminDateRangePicker from '@/Components/Admin/AdminDateRangePicker.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/utils/formatDateTime';
import { paginationMeta } from '@/utils/paginationMeta';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    movements: { type: Object, required: true },
    sessions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const showModal = ref(false);
const sessionId = ref(props.filters.pos_session_id ? String(props.filters.pos_session_id) : '');
const type = ref(props.filters.type ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const meta = computed(() => paginationMeta(props.movements));

const form = useForm({
    pos_session_id: props.filters.pos_session_id || '',
    type: 'in',
    amount: '',
    reason: '',
    notes: '',
});

const money = (value) => Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const visit = (pageNum = 1) => {
    router.get(
        route('pos.cash-management'),
        {
            pos_session_id: sessionId.value || undefined,
            type: type.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: perPage.value,
            page: pageNum > 1 ? pageNum : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const applyDateRange = ({ from, to }) => {
    dateFrom.value = from || '';
    dateTo.value = to || '';
    visit();
};

const setType = (value) => {
    type.value = value;
    visit();
};

watch(sessionId, () => visit());

const save = () => {
    form.post(route('pos.cash-management.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset('amount', 'reason', 'notes');
            form.type = 'in';
        },
    });
};
</script>

<template>
    <Head title="Cash Management" />

    <AdminLayout title="Cash Management">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Cash in / out</h2>
                    <p class="text-xs text-gray-500">{{ meta.total }} movements</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AdminDateRangePicker
                        :from="dateFrom"
                        :to="dateTo"
                        placeholder="Movement date"
                        @update="applyDateRange"
                    />
                    <select v-model="sessionId" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All sessions</option>
                        <option v-for="session in sessions" :key="session.id" :value="String(session.id)">
                            {{ session.label }}
                        </option>
                    </select>
                    <PrimaryButton type="button" class="inline-flex items-center gap-1.5" @click="showModal = true">
                        <ActionIcon name="cash" />
                        Record movement
                    </PrimaryButton>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 border-b border-gray-100 px-4 py-2.5 sm:px-5">
                <button
                    v-for="opt in [
                        { value: '', label: 'All types' },
                        { value: 'in', label: 'Cash in' },
                        { value: 'out', label: 'Cash out' },
                    ]"
                    :key="opt.value || 'all'"
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[11px] font-medium transition ring-1"
                    :class="
                        type === opt.value
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                    "
                    @click="setType(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Session</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in movements.data" :key="row.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ row.session_label }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1"
                                    :class="
                                        row.type === 'in'
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : 'bg-amber-50 text-amber-800 ring-amber-200'
                                    "
                                >
                                    Cash {{ row.type }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell tabular-nums font-semibold">{{ money(row.amount) }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ row.reason || '—' }}</td>
                            <td class="admin-data-table__cell text-sm text-gray-500">{{ formatDateTime(row.created_at) }}</td>
                        </tr>
                        <tr v-if="!movements.data.length">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                                {{ type || sessionId || dateFrom || dateTo ? 'No movements match these filters.' : 'No cash movements yet.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visit()">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="movements" :links="movements.links" />
            </div>
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
                <div>
                    <InputLabel value="Amount" />
                    <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                    <InputError :message="form.errors.amount" />
                </div>
                <div>
                    <InputLabel value="Reason" />
                    <TextInput v-model="form.reason" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
