<script setup>
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminSortableTh from '@/Components/Admin/AdminSortableTh.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DateTimePicker from '@/Components/Admin/DateTimePicker.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
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
    activities: { type: Object, required: true },
    typeOptions: { type: Array, default: () => [] },
    leadOptions: { type: Array, default: () => [] },
    customerOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    listTitle: { type: String, default: 'All Activities' },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const status = ref(props.filters.status ?? '');
const sort = ref(props.filters.sort ?? 'due_at');
const direction = ref(props.filters.direction ?? 'asc');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const deleteTarget = ref(null);
const actionForm = useForm({});
const deleteForm = useForm({});
const meta = computed(() => paginationMeta(props.activities));
const hasActiveFilters = computed(() => Boolean(search.value || type.value || status.value));
const showEmptyState = computed(() => meta.value.total === 0 && !hasActiveFilters.value);

const customerSelectOptions = computed(() =>
    (props.customerOptions ?? []).map((c) => ({
        id: c.id,
        name: c.code ? `${c.name} (${c.code})` : c.name,
    })),
);

const form = useForm({
    type: 'note',
    subject: '',
    body: '',
    due_at: '',
    lead_id: '',
    customer_id: '',
});

const openCreate = () => {
    form.reset();
    form.type = 'note';
    form.due_at = '';
    form.lead_id = '';
    form.customer_id = '';
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    form.post(route('crm.activities.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const complete = (activity) => {
    actionForm.post(route('crm.activities.complete', activity.id), { preserveScroll: true });
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }
    deleteForm.delete(route('crm.activities.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const visitIndex = () => {
    const routeName = props.listTitle === 'Follow-ups' ? 'crm.activities.follow-ups' : 'crm.activities.all';
    router.get(
        route(routeName),
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
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
        direction.value = 'asc';
    }
    visitIndex();
};

const clearFilters = () => {
    search.value = '';
    type.value = '';
    status.value = '';
    visitIndex();
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch([type, status], visitIndex);
</script>

<template>
    <Head :title="listTitle" />

    <AdminLayout :title="listTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <AdminEmptyState
            v-if="showEmptyState"
            title="No activities yet"
            description="Log a call, note, or follow-up and attach it to a lead or customer."
            action-label="Log activity"
            @action="openCreate"
        />

        <div v-else class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">{{ listTitle }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ meta.total }} total · calls, notes, and follow-ups</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="search" type="search" placeholder="Search subject…" class="admin-data-table__search" />
                    </div>
                    <PrimaryButton type="button" @click="openCreate">Log activity</PrimaryButton>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 sm:px-5">
                <select v-model="type" class="admin-filter-select">
                    <option value="">All types</option>
                    <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <select v-model="status" class="admin-filter-select">
                    <option value="">All statuses</option>
                    <option value="open">Open</option>
                    <option value="overdue">Overdue</option>
                    <option value="done">Done</option>
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
                            <AdminSortableTh label="Type" column="type" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <AdminSortableTh label="Subject" column="subject" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th>Related</th>
                            <AdminSortableTh label="Due" column="due_at" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="activity in activities.data" :key="activity.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell text-gray-600">{{ activity.type_label }}</td>
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ activity.subject }}</div>
                                <div v-if="activity.body" class="line-clamp-1 text-xs text-gray-500">{{ activity.body }}</div>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                <div v-if="activity.lead_name">Lead: {{ activity.lead_name }}</div>
                                <div v-if="activity.customer_name">Customer: {{ activity.customer_name }}</div>
                                <div v-if="!activity.lead_name && !activity.customer_name">—</div>
                            </td>
                            <td class="admin-data-table__cell" :class="activity.is_overdue ? 'font-medium text-red-600' : 'text-gray-500'">
                                {{ activity.due_at ? formatDateTime(activity.due_at) : '—' }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                                    :class="activity.completed_at
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                        : activity.is_overdue
                                            ? 'bg-red-50 text-red-700 ring-red-200'
                                            : 'bg-amber-50 text-amber-800 ring-amber-200'"
                                >
                                    {{ activity.completed_at ? 'Done' : activity.is_overdue ? 'Overdue' : 'Open' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        v-if="!activity.completed_at"
                                        type="button"
                                        class="admin-data-table__action text-emerald-700"
                                        title="Complete"
                                        @click="complete(activity)"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button type="button" class="admin-data-table__action admin-data-table__action--danger" title="Delete" @click="deleteTarget = activity">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!activities.data.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No activities match these filters.</td>
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
                <TablePagination :paginator="activities" :links="activities.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="lg" @close="showModal = false">
            <form class="space-y-5 p-6" @submit.prevent="save">
                <div>
                    <h2 class="text-lg font-semibold text-brand-navy">Log activity</h2>
                    <p class="mt-1 text-sm text-gray-500">Pick a due time and link a lead or customer.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Type" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.type"
                                :options="typeOptions"
                                label-key="label"
                                value-key="value"
                                placeholder="Type"
                                :allow-clear="false"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.type" />
                    </div>
                    <div>
                        <InputLabel value="Due at" />
                        <div class="mt-1">
                            <DateTimePicker v-model="form.due_at" mode="datetime" placeholder="Optional due date & time" />
                        </div>
                        <InputError class="mt-1" :message="form.errors.due_at" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Subject" />
                        <TextInput v-model="form.subject" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-1" :message="form.errors.subject" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Details" />
                        <textarea
                            v-model="form.body"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        />
                    </div>
                    <div>
                        <InputLabel value="Lead" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.lead_id"
                                :options="leadOptions"
                                placeholder="None"
                                search-placeholder="Search leads…"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.lead_id" />
                    </div>
                    <div>
                        <InputLabel value="Customer" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.customer_id"
                                :options="customerSelectOptions"
                                placeholder="None"
                                search-placeholder="Search customers…"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.customer_id" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete activity?"
            :item-name="deleteTarget?.subject"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
