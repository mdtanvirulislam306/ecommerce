<script setup>
import AdminDateRangePicker from '@/Components/Admin/AdminDateRangePicker.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminExportMenu from '@/Components/Admin/AdminExportMenu.vue';
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
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    leads: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
    assignees: { type: Array, default: () => [] },
    stageOptions: { type: Array, default: () => [] },
    moveStageOptions: { type: Array, default: () => [] },
    stageCounts: { type: Object, default: () => ({}) },
    activityTypeOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    scope: { type: String, default: 'all' },
    view: { type: String, default: 'all' },
    listTitle: { type: String, default: 'Leads' },
    openCreate: { type: Boolean, default: false },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const stage = ref(props.filters.stage ?? '');
const sourceId = ref(props.filters.source_id ?? '');
const assignedTo = ref(props.filters.assigned_to ?? '');
const converted = ref(props.filters.converted ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const sort = ref(props.filters.sort ?? 'created_at');
const direction = ref(props.filters.direction ?? 'desc');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const convertTarget = ref(null);
const scheduleTarget = ref(null);
const actionForm = useForm({});

const sourceOptions = ref([...(props.sources ?? [])]);
const groupOptions = ref([...(props.groups ?? [])]);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    source: '',
    source_id: '',
    stage: 'new',
    customer_group_id: '',
    assigned_to: '',
    notes: '',
});

const deleteForm = useForm({});
const scheduleForm = useForm({
    type: 'call',
    subject: '',
    body: '',
    due_at: '',
    lead_id: '',
    customer_id: '',
});
const quickModal = ref(null);
const quickForm = useForm({ name: '', is_active: true });
const quickError = ref('');

const stageMeta = {
    new: { class: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' },
    contacted: { class: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' },
    qualified: { class: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' },
    proposal: { class: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200' },
    won: { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    lost: { class: 'bg-red-50 text-red-700 ring-1 ring-red-200' },
};

const creatableStages = computed(() =>
    props.stageOptions.filter((x) => !['won', 'lost'].includes(x.value)),
);

const meta = computed(() => paginationMeta(props.leads));
const hasLeads = computed(() => meta.value.total > 0);
const isPipeline = computed(() => props.view === 'pipeline');
const isMine = computed(() => props.scope === 'mine');
const pipelineTotal = computed(() =>
    (props.stageOptions ?? []).reduce((sum, item) => sum + (props.stageCounts[item.value] || 0), 0),
);
const hasActiveFilters = computed(
    () => Boolean(search.value || stage.value || sourceId.value || assignedTo.value || converted.value || dateFrom.value || dateTo.value),
);
const showEmptyState = computed(() => !hasLeads.value && !hasActiveFilters.value);

const queryParams = () => ({
    search: search.value || undefined,
    stage: stage.value || undefined,
    source_id: sourceId.value || undefined,
    assigned_to: isMine.value ? undefined : assignedTo.value || undefined,
    converted: isPipeline.value ? undefined : converted.value || undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
    scope: isMine.value ? 'mine' : undefined,
    view: isPipeline.value ? 'pipeline' : undefined,
    sort: sort.value,
    direction: direction.value,
    per_page: perPage.value,
});

const visitIndex = () => {
    router.get(route('crm.leads.all'), queryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setScope = (next) => {
    assignedTo.value = '';
    router.get(route('crm.leads.all'), {
        ...queryParams(),
        scope: next === 'mine' ? 'mine' : undefined,
        assigned_to: undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setView = (next) => {
    converted.value = '';
    stage.value = '';
    router.get(route('crm.leads.all'), {
        ...queryParams(),
        view: next === 'pipeline' ? 'pipeline' : undefined,
        converted: undefined,
        stage: undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const filterByStage = (value) => {
    stage.value = stage.value === value ? '' : value;
    visitIndex();
};

const toggleSort = (column) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = column === 'name' || column === 'company' || column === 'next_action' ? 'asc' : 'desc';
    }
    visitIndex();
};

const clearFilters = () => {
    search.value = '';
    stage.value = '';
    sourceId.value = '';
    assignedTo.value = '';
    converted.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    visitIndex();
};

const applyDateRange = ({ from, to }) => {
    dateFrom.value = from || '';
    dateTo.value = to || '';
    visitIndex();
};

const exportUrl = (format) => route('crm.leads.export', { ...queryParams(), format });

const openCreateModal = () => {
    editing.value = null;
    form.reset();
    form.stage = 'new';
    form.source_id = '';
    form.customer_group_id = '';
    form.assigned_to = '';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (lead) => {
    editing.value = lead;
    form.name = lead.name;
    form.email = lead.email || '';
    form.phone = lead.phone || '';
    form.company = lead.company || '';
    form.source = lead.source || '';
    form.source_id = lead.source_id || '';
    form.stage = lead.stage;
    form.customer_group_id = lead.customer_group_id || '';
    form.assigned_to = lead.assigned_to || '';
    form.notes = lead.notes || '';
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
        form.put(route('crm.leads.update', editing.value.id), options);
    } else {
        form.post(route('crm.leads.store'), options);
    }
};

const confirmConvert = (createOrder = false) => {
    if (!convertTarget.value) {
        return;
    }

    actionForm
        .transform(() => ({ create_order: createOrder ? 1 : 0 }))
        .post(route('crm.leads.convert', convertTarget.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                convertTarget.value = null;
            },
            onFinish: () => actionForm.transform((data) => data),
        });
};

const moveStage = (lead, nextStage) => {
    if (!nextStage || lead.stage === nextStage) {
        return;
    }

    actionForm.transform(() => ({ stage: nextStage })).post(route('crm.leads.stage', lead.id), {
        preserveScroll: true,
        onFinish: () => actionForm.transform((data) => data),
    });
};

const openSchedule = (lead) => {
    scheduleTarget.value = lead;
    scheduleForm.reset();
    scheduleForm.type = 'call';
    scheduleForm.subject = `Follow up: ${lead.name}`;
    scheduleForm.due_at = '';
    scheduleForm.lead_id = lead.id;
    scheduleForm.customer_id = '';
    scheduleForm.clearErrors();
};

const saveSchedule = () => {
    scheduleForm.post(route('crm.activities.store'), {
        preserveScroll: true,
        onSuccess: () => {
            scheduleTarget.value = null;
        },
    });
};

const completeNextAction = (lead) => {
    if (!lead.next_action?.id) {
        return;
    }

    actionForm.post(route('crm.activities.complete', lead.next_action.id), {
        preserveScroll: true,
    });
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    deleteForm.delete(route('crm.leads.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const openQuick = (type) => {
    quickModal.value = type;
    quickForm.reset();
    quickForm.is_active = true;
    quickForm.clearErrors();
    quickError.value = '';
};

const quickRoute = computed(() => {
    if (quickModal.value === 'source') {
        return route('crm.leads.sources.quick');
    }
    if (quickModal.value === 'group') {
        return route('commerce.pricing.customer-groups.quick');
    }

    return null;
});

const quickTitle = computed(() => {
    if (quickModal.value === 'source') {
        return 'Add lead source';
    }
    if (quickModal.value === 'group') {
        return 'Add customer group';
    }

    return '';
});

const submitQuick = async () => {
    quickError.value = '';
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    try {
        const res = await fetch(quickRoute.value, {
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
        const item = data.item;
        if (quickModal.value === 'source') {
            sourceOptions.value = [...sourceOptions.value, item];
            form.source_id = item.id;
        }
        if (quickModal.value === 'group') {
            groupOptions.value = [...groupOptions.value, item];
            form.customer_group_id = item.id;
        }
        quickModal.value = null;
    } catch {
        quickError.value = 'Network error — try again';
    }
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch([stage, sourceId, assignedTo, converted], visitIndex);

onMounted(() => {
    if (props.openCreate) {
        openCreateModal();
    }
});
</script>

<template>
    <Head :title="listTitle" />

    <AdminLayout :title="listTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="!isMine ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-600 hover:text-brand-navy'"
                        @click="setScope('all')"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="isMine ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-600 hover:text-brand-navy'"
                        @click="setScope('mine')"
                    >
                        Mine
                    </button>
                </div>
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="!isPipeline ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-600 hover:text-brand-navy'"
                        @click="setView('all')"
                    >
                        All stages
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="isPipeline ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-600 hover:text-brand-navy'"
                        @click="setView('pipeline')"
                    >
                        Pipeline
                    </button>
                </div>
            </div>
            <div v-if="isPipeline" class="flex flex-wrap gap-2">
                <button
                    v-for="s in stageOptions"
                    :key="s.value"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="stage === s.value
                        ? 'bg-brand-navy text-white'
                        : 'bg-white text-brand-navy ring-1 ring-gray-200 hover:bg-gray-50'"
                    @click="filterByStage(s.value)"
                >
                    {{ s.label }}
                    <span
                        class="rounded-full px-1.5 py-0.5 text-[10px]"
                        :class="stage === s.value ? 'bg-white/20' : 'bg-gray-100 text-gray-600'"
                    >
                        {{ stageCounts[s.value] || 0 }}
                    </span>
                </button>
            </div>
        </div>

        <div v-if="showEmptyState" class="space-y-4">
            <div class="flex flex-wrap items-center justify-end gap-2">
                <AdminDateRangePicker
                    :from="dateFrom"
                    :to="dateTo"
                    placeholder="Created date range"
                    @update="applyDateRange"
                />
                <AdminExportMenu
                    :csv-url="exportUrl('csv')"
                    :pdf-url="exportUrl('pdf')"
                    :print-url="exportUrl('print')"
                />
            </div>
            <AdminEmptyState
                :title="isPipeline ? 'Pipeline is empty' : 'No leads yet'"
                :description="isPipeline
                    ? 'Add a lead and move it through New → Contacted → Qualified → Proposal → Won.'
                    : 'Capture a prospect and move them through New → Contacted → Qualified → Proposal → Won.'"
                action-label="Add lead"
                @action="openCreateModal"
            />
        </div>

        <div v-else class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">{{ listTitle }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        <template v-if="isPipeline">
                            {{ meta.total }} matching · {{ pipelineTotal }} in pipeline · lost leads hidden
                        </template>
                        <template v-else>
                            {{ meta.total }} total · filter, sort, then convert winners
                        </template>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="search" type="search" placeholder="Search name, email, company…" class="admin-data-table__search" />
                    </div>
                    <AdminExportMenu
                        :csv-url="exportUrl('csv')"
                        :pdf-url="exportUrl('pdf')"
                        :print-url="exportUrl('print')"
                    />
                    <PrimaryButton type="button" @click="openCreateModal">Add lead</PrimaryButton>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 sm:px-5">
                <select v-model="stage" class="admin-filter-select">
                    <option value="">{{ isPipeline ? 'All pipeline stages' : 'All stages' }}</option>
                    <option v-for="s in stageOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="sourceId" class="admin-filter-select">
                    <option value="">All sources</option>
                    <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-if="!isMine" v-model="assignedTo" class="admin-filter-select">
                    <option value="">All assignees</option>
                    <option v-for="user in assignees" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
                <select v-if="!isPipeline" v-model="converted" class="admin-filter-select">
                    <option value="">All statuses</option>
                    <option value="no">Open</option>
                    <option value="yes">Converted</option>
                </select>
                <AdminDateRangePicker
                    :from="dateFrom"
                    :to="dateTo"
                    placeholder="Created date range"
                    @update="applyDateRange"
                />
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
                            <th>Contact</th>
                            <AdminSortableTh label="Stage" column="stage" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <AdminSortableTh label="Next action" column="next_action" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <AdminSortableTh label="Source" column="source" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th>Owner</th>
                            <AdminSortableTh label="Created" column="created_at" :sort="sort" :direction="direction" @sort="toggleSort" />
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in leads.data" :key="lead.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <p class="font-medium text-brand-navy">{{ lead.name }}</p>
                                <p v-if="lead.company" class="mt-0.5 text-xs text-gray-500">{{ lead.company }}</p>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                <div>{{ lead.email || '—' }}</div>
                                <div>{{ lead.phone || '' }}</div>
                            </td>
                            <td class="admin-data-table__cell">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="stageMeta[lead.stage]?.class">
                                        {{ lead.stage_label }}
                                    </span>
                                    <select
                                        v-if="lead.stage !== 'won'"
                                        class="admin-filter-select py-1 text-xs"
                                        :value="lead.stage"
                                        @change="moveStage(lead, $event.target.value)"
                                    >
                                        <option v-for="s in moveStageOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                                    </select>
                                </div>
                            </td>
                            <td class="admin-data-table__cell">
                                <div v-if="lead.next_action" class="max-w-[14rem]">
                                    <p
                                        class="truncate text-sm font-medium"
                                        :class="lead.next_action.is_overdue ? 'text-red-600' : 'text-brand-navy'"
                                        :title="lead.next_action.subject"
                                    >
                                        {{ lead.next_action.subject }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs"
                                        :class="lead.next_action.is_overdue ? 'text-red-500' : 'text-gray-500'"
                                    >
                                        {{ lead.next_action.type_label }} · {{ formatDateTime(lead.next_action.due_at) }}
                                        <span v-if="lead.next_action.is_overdue"> · Overdue</span>
                                    </p>
                                    <button
                                        type="button"
                                        class="mt-1 text-xs font-medium text-brand-orange hover:underline"
                                        :disabled="actionForm.processing"
                                        @click="completeNextAction(lead)"
                                    >
                                        Mark done
                                    </button>
                                </div>
                                <button
                                    v-else-if="!['won', 'lost'].includes(lead.stage)"
                                    type="button"
                                    class="text-xs font-medium text-brand-orange hover:underline"
                                    @click="openSchedule(lead)"
                                >
                                    Schedule
                                </button>
                                <span v-else class="text-sm text-gray-400">—</span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ lead.source || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ lead.assigned_to_name || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(lead.created_at) }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button
                                        v-if="lead.can_convert"
                                        type="button"
                                        class="admin-data-table__action text-emerald-700"
                                        title="Convert to customer"
                                        @click="convertTarget = lead"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="lead.stage === 'new'"
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Mark contacted"
                                        @click="moveStage(lead, 'contacted')"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="!['won', 'lost'].includes(lead.stage)"
                                        type="button"
                                        class="admin-data-table__action"
                                        title="Edit"
                                        @click="openEdit(lead)"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="!lead.converted_customer_id && lead.stage !== 'won'"
                                        type="button"
                                        class="admin-data-table__action admin-data-table__action--danger"
                                        title="Delete"
                                        @click="deleteTarget = lead"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!leads.data.length">
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">No leads match these filters.</td>
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
                <TablePagination :paginator="leads" :links="leads.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <form class="space-y-5 p-6" @submit.prevent="save">
                <div>
                    <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit lead' : 'Add lead' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Assign an owner so follow-ups stay on someone’s list.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel value="Name" />
                        <TextInput v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-1" :message="form.errors.name" />
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
                    <div>
                        <InputLabel value="Source" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.source_id"
                                :options="sourceOptions"
                                placeholder="Select source…"
                                search-placeholder="Search sources…"
                                creatable
                                create-label="Add source"
                                @create="openQuick('source')"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.source_id" />
                    </div>
                    <div>
                        <InputLabel value="Owner" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.assigned_to"
                                :options="assignees"
                                placeholder="Unassigned"
                                search-placeholder="Search users…"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.assigned_to" />
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
                                @create="openQuick('group')"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.customer_group_id" />
                    </div>
                    <div v-if="!editing">
                        <InputLabel value="Stage" />
                        <div class="mt-1">
                            <SearchableSelect
                                v-model="form.stage"
                                :options="creatableStages"
                                label-key="label"
                                value-key="value"
                                placeholder="Stage"
                                :allow-clear="false"
                            />
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Notes" />
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            placeholder="Context, next step…"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="Boolean(quickModal)" max-width="sm" @close="quickModal = null">
            <form class="space-y-4 p-6" @submit.prevent="submitQuick">
                <h3 class="text-base font-semibold text-brand-navy">{{ quickTitle }}</h3>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="quickForm.name" class="mt-1 block w-full" required autofocus />
                    <p v-if="quickError" class="mt-1 text-sm text-red-600">{{ quickError }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="quickModal = null">Cancel</SecondaryButton>
                    <PrimaryButton type="submit">Create</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="Boolean(convertTarget)" max-width="sm" @close="convertTarget = null">
            <div class="p-6">
                <h3 class="text-base font-semibold text-brand-navy">Convert to customer?</h3>
                <p class="mt-2 text-sm text-gray-500">
                    {{ convertTarget?.name }} will become a customer and this lead will be marked Won.
                </p>
                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <SecondaryButton type="button" @click="convertTarget = null">Cancel</SecondaryButton>
                    <SecondaryButton type="button" :disabled="actionForm.processing" @click="confirmConvert(false)">
                        Convert only
                    </SecondaryButton>
                    <PrimaryButton type="button" :disabled="actionForm.processing" @click="confirmConvert(true)">
                        Convert & create order
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="Boolean(scheduleTarget)" max-width="md" @close="scheduleTarget = null">
            <form class="space-y-4 p-6" @submit.prevent="saveSchedule">
                <div>
                    <h3 class="text-base font-semibold text-brand-navy">Schedule next action</h3>
                    <p class="mt-1 text-sm text-gray-500">Follow-up for {{ scheduleTarget?.name }}</p>
                </div>
                <div>
                    <InputLabel value="Type" />
                    <select
                        v-model="scheduleForm.type"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    >
                        <option v-for="opt in activityTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Subject" />
                    <TextInput v-model="scheduleForm.subject" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="scheduleForm.errors.subject" />
                </div>
                <div>
                    <InputLabel value="Due at" />
                    <DateTimePicker v-model="scheduleForm.due_at" class="mt-1" />
                    <InputError class="mt-1" :message="scheduleForm.errors.due_at" />
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <SecondaryButton type="button" @click="scheduleTarget = null">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="scheduleForm.processing">Schedule</PrimaryButton>
                </div>
            </form>
        </Modal>

        <DeleteConfirmModal
            :show="Boolean(deleteTarget)"
            title="Delete lead?"
            :item-name="deleteTarget?.name"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AdminLayout>
</template>
