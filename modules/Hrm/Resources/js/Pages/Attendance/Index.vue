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
    attendances: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
    employee_id: null,
    date: '',
    check_in: '',
    check_out: '',
    status: 'present',
    notes: '',
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.employee_id = row.employee_id ?? null;
    form.date = row.date ?? '';
    form.check_in = row.check_in ?? '';
    form.check_out = row.check_out ?? '';
    form.status = row.status ?? 'present';
    form.notes = row.notes ?? '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('hrm.attendance.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('hrm.attendance.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route('hrm.attendance.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Attendance" />

    <AdminLayout title="Attendance">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="pb-2">Employee</th>
                        <th class="pb-2">Date</th>
                        <th class="pb-2">In</th>
                        <th class="pb-2">Out</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in attendances.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2">{{ row.employee_name ?? '—' }}</td>
                        <td class="py-2">{{ row.date ?? '—' }}</td>
                        <td class="py-2">{{ row.check_in ?? '—' }}</td>
                        <td class="py-2">{{ row.check_out ?? '—' }}</td>
                        <td class="py-2">{{ row.status ?? '—' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="attendances" class="mt-4" />
        </section>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit' : 'Add' }}</h2>
                <div>
                    <InputLabel value="Employee" />
                    <select v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">—</option>
                        <option v-for="opt in options.employees" :key="opt.value ?? opt.id ?? opt" :value="opt.value ?? opt.id ?? opt">{{ opt.label ?? opt.name ?? opt }}</option>
                    </select>
                    <InputError :message="form.errors.employee_id" />
                </div>
                <div>
                    <InputLabel value="Date" />
                    <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.date" />
                </div>
                <div>
                    <InputLabel value="Check in" />
                    <TextInput v-model="form.check_in" type="time" class="mt-1 block w-full" />
                    <InputError :message="form.errors.check_in" />
                </div>
                <div>
                    <InputLabel value="Check out" />
                    <TextInput v-model="form.check_out" type="time" class="mt-1 block w-full" />
                    <InputError :message="form.errors.check_out" />
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">—</option>
                        <option v-for="opt in options.statuses" :key="opt.value ?? opt.id ?? opt" :value="opt.value ?? opt.id ?? opt">{{ opt.label ?? opt.name ?? opt }}</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div>
                    <InputLabel value="Notes" />
                    <textarea v-model="form.notes" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="3" />
                    <InputError :message="form.errors.notes" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete this record?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('hrm.attendance.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>