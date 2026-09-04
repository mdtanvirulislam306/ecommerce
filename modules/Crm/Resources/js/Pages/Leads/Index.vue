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
import { formatDateTime } from '@/utils/formatDateTime';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    leads: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    sources: { type: Array, default: () => [] },
    stageOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    listTitle: { type: String, default: 'All Leads' },
    openCreate: { type: Boolean, default: false },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const stage = ref(props.filters.stage ?? '');
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
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
    notes: '',
});

const deleteForm = useForm({});
const quickModal = ref(null); // 'source' | 'group' | null
const quickForm = useForm({ name: '', is_active: true });
const quickError = ref('');

const stageMeta = {
    new: { class: 'bg-gray-100 text-gray-600' },
    contacted: { class: 'bg-sky-50 text-sky-700' },
    qualified: { class: 'bg-amber-50 text-amber-800' },
    proposal: { class: 'bg-violet-50 text-violet-700' },
    won: { class: 'bg-emerald-50 text-emerald-700' },
    lost: { class: 'bg-red-50 text-red-700' },
};

const creatableStages = computed(() =>
    props.stageOptions.filter((x) => !['won', 'lost'].includes(x.value)),
);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.stage = 'new';
    form.source_id = '';
    form.customer_group_id = '';
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

const convertLead = (lead) => {
    if (!confirm(`Convert ${lead.name} to a customer?`)) return;
    actionForm.post(route('crm.leads.convert', lead.id), { preserveScroll: true });
};

const moveStage = (lead, nextStage) => {
    actionForm.transform(() => ({ stage: nextStage })).post(route('crm.leads.stage', lead.id), {
        preserveScroll: true,
        onFinish: () => actionForm.transform((data) => data),
    });
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
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
    if (quickModal.value === 'source') return route('crm.leads.sources.quick');
    if (quickModal.value === 'group') return route('commerce.pricing.customer-groups.quick');
    return null;
});

const quickTitle = computed(() => {
    if (quickModal.value === 'source') return 'Add lead source';
    if (quickModal.value === 'group') return 'Add customer group';
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

const visitIndex = () => {
    const routeName = props.listTitle === 'My Leads' ? 'crm.leads.my' : 'crm.leads.all';
    router.get(
        route(routeName),
        {
            search: search.value || undefined,
            stage: stage.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(visitIndex, 300);
});
watch(stage, visitIndex);

onMounted(() => {
    if (props.openCreate) {
        openCreate();
    }
});
</script>

<template>
    <Head :title="listTitle" />

    <AdminLayout :title="listTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">{{ listTitle }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Track prospects — sources and groups can be added inline.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search leads…" class="admin-data-table__search" />
                    <select v-model="stage" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All stages</option>
                        <option v-for="s in stageOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <PrimaryButton type="button" @click="openCreate">Add lead</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Stage</th>
                            <th>Source</th>
                            <th>Group</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in leads.data" :key="lead.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">
                                {{ lead.name }}
                                <div v-if="lead.company" class="text-xs font-normal text-gray-500">{{ lead.company }}</div>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                <div>{{ lead.email || '—' }}</div>
                                <div>{{ lead.phone }}</div>
                            </td>
                            <td class="admin-data-table__cell">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="stageMeta[lead.stage]?.class">
                                    {{ lead.stage_label }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ lead.source || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-600">{{ lead.customer_group_name || '—' }}</td>
                            <td class="admin-data-table__cell text-gray-500">{{ formatDateTime(lead.created_at) }}</td>
                            <td class="admin-data-table__cell text-right">
                                <button
                                    v-if="lead.can_convert"
                                    type="button"
                                    class="admin-data-table__action text-emerald-700"
                                    @click="convertLead(lead)"
                                >
                                    Convert
                                </button>
                                <button
                                    v-if="lead.stage === 'new'"
                                    type="button"
                                    class="admin-data-table__action"
                                    @click="moveStage(lead, 'contacted')"
                                >
                                    Contact
                                </button>
                                <button
                                    v-if="!['won', 'lost'].includes(lead.stage)"
                                    type="button"
                                    class="admin-data-table__action"
                                    @click="openEdit(lead)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="!lead.converted_customer_id && lead.stage !== 'won'"
                                    type="button"
                                    class="admin-data-table__action text-red-600"
                                    @click="deleteTarget = lead"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!leads.data.length">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">No leads yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
                <TablePagination :paginator="leads" :links="leads.links" />
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <form class="space-y-5 p-6" @submit.prevent="save">
                <div>
                    <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit lead' : 'Add lead' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Contact details first — source and group via searchable +.</p>
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
