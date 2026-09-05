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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    channel: { type: String, required: true },
    channelLabel: { type: String, required: true },
    campaigns: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    crmSegments: { type: Array, default: () => [] },
    routes: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const actionForm = useForm({});

const form = useForm({
    name: '',
    status: 'draft',
    subject: '',
    body: '',
    audience_count: 0,
    customer_segment_id: '',
    scheduled_at: '',
});

const deleteForm = useForm({});
const linkedToCrm = computed(() => Boolean(form.customer_segment_id));

const applyCrmSegment = () => {
    const match = props.crmSegments.find((row) => String(row.id) === String(form.customer_segment_id));
    if (match) {
        form.audience_count = match.customers_count || 0;
    }
};

watch(() => form.customer_segment_id, applyCrmSegment);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.status = 'draft';
    form.customer_segment_id = '';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.name = row.name;
    form.status = row.status;
    form.subject = row.subject || '';
    form.body = row.body || '';
    form.audience_count = row.audience_count || 0;
    form.customer_segment_id = row.customer_segment_id || '';
    form.scheduled_at = row.scheduled_at ? row.scheduled_at.slice(0, 16) : '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route(props.routes.update, editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route(props.routes.store), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route(props.routes.index), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head :title="`${channelLabel} Campaigns`" />

    <AdminLayout :title="`${channelLabel} Campaigns`">
        <p class="mb-4 text-sm text-gray-500">
            {{ channelLabel }} campaign drafts. Provider send is stubbed — use Mark sent for bookkeeping.
        </p>

        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search campaigns…" />
            <PrimaryButton type="button" @click="openCreate">New {{ channelLabel.toLowerCase() }} campaign</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Name</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2">Audience</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in campaigns.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium text-brand-navy">{{ row.name }}</td>
                        <td class="py-2">{{ row.status_label }}</td>
                        <td class="py-2">
                            <span>{{ row.audience_count }}</span>
                            <span v-if="row.customer_segment_name" class="ml-1 text-xs text-gray-400">({{ row.customer_segment_name }})</span>
                        </td>

                        <td class="py-2 text-right space-x-2">
                            <button
                                v-if="row.status !== 'sent'"
                                type="button"
                                class="text-xs text-emerald-700"
                                @click="actionForm.post(route(routes.markSent, row.id))"
                            >
                                Mark sent
                            </button>
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!campaigns.data.length">
                        <td colspan="4" class="py-8 text-center text-gray-500">No {{ channelLabel.toLowerCase() }} campaigns yet.</td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="campaigns" class="mt-4" />
        </section>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit campaign' : `New ${channelLabel} campaign` }}</h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" />
                </div>
                <div v-if="editing">
                    <InputLabel value="Status" />
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Subject" />
                    <TextInput v-model="form.subject" class="mt-1 block w-full" />
                </div>
                <div>
                    <InputLabel value="Body" />
                    <textarea v-model="form.body" rows="5" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <InputLabel value="CRM audience segment" />
                    <select v-model="form.customer_segment_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Manual audience count</option>
                        <option v-for="seg in crmSegments" :key="seg.id" :value="seg.id">
                            {{ seg.name }} ({{ seg.customers_count }})
                        </option>
                    </select>
                </div>
                <div>
                    <InputLabel :value="linkedToCrm ? 'Audience count (from CRM)' : 'Audience count'" />
                    <TextInput
                        v-model="form.audience_count"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                        :disabled="linkedToCrm"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete campaign?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route(routes.destroy, deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
