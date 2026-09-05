<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    segments: { type: Object, required: true },
    crmSegments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    name: '',
    description: '',
    customer_segment_id: '',
    customer_count: 0,
    is_active: true,
});

const deleteForm = useForm({});
const linkedToCrm = computed(() => Boolean(form.customer_segment_id));

const applyCrmSegment = () => {
    const match = props.crmSegments.find((row) => String(row.id) === String(form.customer_segment_id));
    if (!match) {
        return;
    }
    if (!form.name) {
        form.name = match.name;
    }
    form.customer_count = match.customers_count || 0;
};

watch(() => form.customer_segment_id, applyCrmSegment);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.customer_segment_id = '';
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.name = row.name;
    form.description = row.description || '';
    form.customer_segment_id = row.customer_segment_id || '';
    form.customer_count = row.customer_count || 0;
    form.is_active = row.is_active;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('marketing.segments.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('marketing.segments.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route('marketing.segments.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Segments" />

    <AdminLayout title="Audience Segments">
        <p class="mb-4 text-sm text-gray-500">
            Link a CRM customer segment for a live audience count, or enter a manual count.
            <Link :href="route('crm.customers.segments')" class="font-medium text-brand-orange hover:underline">Manage CRM segments</Link>
        </p>

        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search segments…" />
            <PrimaryButton type="button" @click="openCreate">New segment</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Name</th>
                        <th class="pb-2">CRM source</th>
                        <th class="pb-2">Customers</th>
                        <th class="pb-2">Active</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in segments.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2">
                            <p class="font-medium text-brand-navy">{{ row.name }}</p>
                            <p v-if="row.description" class="text-xs text-gray-500">{{ row.description }}</p>
                        </td>
                        <td class="py-2 text-gray-600">
                            <span v-if="row.is_linked_to_crm">{{ row.customer_segment_name }} ({{ row.customer_segment_code }})</span>
                            <span v-else class="text-gray-400">Manual</span>
                        </td>
                        <td class="py-2">{{ row.customer_count }}</td>
                        <td class="py-2">{{ row.is_active ? 'Yes' : 'No' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="segments" class="mt-4" />
        </section>

        <Modal :show="showModal" @close="showModal = false">
            <div class="space-y-3 p-6">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit segment' : 'New segment' }}</h2>
                <div>
                    <InputLabel value="CRM customer segment" />
                    <select v-model="form.customer_segment_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Manual audience (no CRM link)</option>
                        <option v-for="seg in crmSegments" :key="seg.id" :value="seg.id">
                            {{ seg.name }} ({{ seg.customers_count }} customers)
                        </option>
                    </select>
                    <InputError :message="form.errors.customer_segment_id" />
                </div>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel value="Description" />
                    <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <InputLabel :value="linkedToCrm ? 'Audience count (from CRM)' : 'Customer count'" />
                    <TextInput
                        v-model="form.customer_count"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                        :disabled="linkedToCrm"
                    />
                    <p v-if="linkedToCrm" class="mt-1 text-xs text-gray-500">Count syncs from the CRM segment membership.</p>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model:checked="form.is_active" />
                    Active
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete segment?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('marketing.segments.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
