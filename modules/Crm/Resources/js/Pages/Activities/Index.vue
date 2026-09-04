<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DateTimePicker from '@/Components/Admin/DateTimePicker.vue';
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
const perPage = ref(props.filters.per_page ?? 25);
const showModal = ref(false);
const actionForm = useForm({});

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

const remove = (activity) => {
    if (!confirm('Delete this activity?')) return;
    actionForm.delete(route('crm.activities.destroy', activity.id), { preserveScroll: true });
};

const visitIndex = () => {
    const routeName = props.listTitle === 'Follow-ups' ? 'crm.activities.follow-ups' : 'crm.activities.all';
    router.get(
        route(routeName),
        {
            search: search.value || undefined,
            type: type.value || undefined,
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
watch(type, visitIndex);
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
                    <p class="mt-0.5 text-xs text-gray-500">Calls, notes, and follow-ups linked to leads or customers.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input v-model="search" type="search" placeholder="Search…" class="admin-data-table__search" />
                    <select v-model="type" class="rounded-lg border border-gray-200 text-xs">
                        <option value="">All types</option>
                        <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <PrimaryButton type="button" @click="openCreate">Log activity</PrimaryButton>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Related</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="activity in activities.data" :key="activity.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell text-gray-600">{{ activity.type_label }}</td>
                            <td class="admin-data-table__cell">
                                <div class="font-medium text-brand-navy">{{ activity.subject }}</div>
                                <div v-if="activity.body" class="text-xs text-gray-500">{{ activity.body }}</div>
                            </td>
                            <td class="admin-data-table__cell text-sm text-gray-500">
                                <div v-if="activity.lead_name">Lead: {{ activity.lead_name }}</div>
                                <div v-if="activity.customer_name">Customer: {{ activity.customer_name }}</div>
                                <div v-if="!activity.lead_name && !activity.customer_name">—</div>
                            </td>
                            <td class="admin-data-table__cell text-gray-500">
                                {{ activity.due_at ? formatDateTime(activity.due_at) : '—' }}
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="activity.completed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                >
                                    {{ activity.completed_at ? 'Done' : 'Open' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-right">
                                <button
                                    v-if="!activity.completed_at"
                                    type="button"
                                    class="admin-data-table__action text-emerald-700"
                                    @click="complete(activity)"
                                >
                                    Complete
                                </button>
                                <button type="button" class="admin-data-table__action text-red-600" @click="remove(activity)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!activities.data.length">
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No activities yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-data-table__footer">
                <select v-model.number="perPage" class="rounded-lg border border-gray-200 text-xs" @change="visitIndex">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} per page</option>
                </select>
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
    </AdminLayout>
</template>
