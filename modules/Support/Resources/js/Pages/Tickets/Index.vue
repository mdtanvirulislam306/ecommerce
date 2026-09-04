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
    tickets: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    pageTitle: { type: String, default: 'Support tickets' },
    listRoute: { type: String, default: 'support.tickets.index' },
    allowCreate: { type: Boolean, default: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    subject: '',
    body: '',
    priority: 'normal',
    status: 'open',
    requester_name: '',
    requester_email: '',
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.priority = 'normal';
    form.status = 'open';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.subject = row.subject;
    form.body = row.body || '';
    form.priority = row.priority || 'normal';
    form.status = row.status || 'open';
    form.requester_name = row.requester_name || '';
    form.requester_email = row.requester_email || '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('support.tickets.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('support.tickets.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

const applyFilters = () => {
    router.get(
        route(props.listRoute),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
};

watch([search, status], applyFilters);
</script>

<template>
    <Head :title="pageTitle" />

    <AdminLayout :title="pageTitle">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <TextInput v-model="search" type="search" class="w-56" placeholder="Search tickets…" />
                <select v-model="status" class="rounded-md border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
            <PrimaryButton v-if="allowCreate" type="button" @click="openCreate">New ticket</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Number</th>
                        <th class="pb-2">Subject</th>
                        <th class="pb-2">Priority</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in tickets.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 text-gray-500">{{ row.number }}</td>
                        <td class="py-2 font-medium text-brand-navy">{{ row.subject }}</td>
                        <td class="py-2">{{ row.priority_label }}</td>
                        <td class="py-2">{{ row.status_label }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="tickets" class="mt-4" />
        </section>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit ticket' : 'New ticket' }}</h2>
                <div>
                    <InputLabel value="Subject" />
                    <TextInput v-model="form.subject" class="mt-1 block w-full" />
                    <InputError :message="form.errors.subject" />
                </div>
                <div>
                    <InputLabel value="Details" />
                    <textarea v-model="form.body" rows="4" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Priority" />
                        <select v-model="form.priority" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div v-if="editing">
                        <InputLabel value="Status" />
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Requester name" />
                        <TextInput v-model="form.requester_name" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Requester email" />
                        <TextInput v-model="form.requester_email" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete ticket?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('support.tickets.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
