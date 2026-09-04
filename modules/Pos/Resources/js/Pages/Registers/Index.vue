<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    registers: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const showModal = ref(false);
const editing = ref(null);
const openTarget = ref(null);
const closeTarget = ref(null);

const form = useForm({
    name: '',
    code: '',
    warehouse_id: '',
    is_default: false,
    is_active: true,
    sort_order: 0,
});

const openForm = useForm({ opening_cash: 0 });
const closeForm = useForm({ closing_cash: 0 });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (register) => {
    editing.value = register;
    form.name = register.name;
    form.code = register.code;
    form.warehouse_id = register.warehouse_id || '';
    form.is_default = register.is_default;
    form.is_active = register.is_active;
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
        form.put(route('pos.registers.update', editing.value.id), options);
    } else {
        form.post(route('pos.registers.store'), options);
    }
};

const submitOpen = () => {
    if (!openTarget.value) return;
    openForm.post(route('pos.registers.open-session', openTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            openTarget.value = null;
        },
    });
};

const submitClose = () => {
    if (!closeTarget.value?.open_session_id) return;
    closeForm.post(route('pos.sessions.close', closeTarget.value.open_session_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="POS Registers" />

    <AdminLayout title="POS Registers">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <h2 class="text-sm font-semibold text-brand-navy">Registers</h2>
                <PrimaryButton type="button" @click="openCreate">Add register</PrimaryButton>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Name</th>
                            <th>Code</th>
                            <th>Session</th>
                            <th>Default</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="register in registers" :key="register.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell font-medium text-brand-navy">{{ register.name }}</td>
                            <td class="admin-data-table__cell">{{ register.code }}</td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="register.has_open_session ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ register.has_open_session ? 'Open' : 'Closed' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">{{ register.is_default ? 'Yes' : '—' }}</td>
                            <td class="admin-data-table__cell text-right">
                                <button type="button" class="admin-data-table__action" @click="openEdit(register)">Edit</button>
                                <button
                                    v-if="!register.has_open_session"
                                    type="button"
                                    class="admin-data-table__action text-emerald-700"
                                    @click="openTarget = register"
                                >
                                    Open session
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="admin-data-table__action text-amber-700"
                                    @click="closeTarget = register"
                                >
                                    Close session
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit register' : 'Add register' }}</h2>
                <div>
                    <InputLabel value="Name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel value="Code" />
                    <TextInput v-model="form.code" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.code" />
                </div>
                <div>
                    <InputLabel value="Warehouse" />
                    <select v-model="form.warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Default warehouse</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_default" /> Default register</label>
                <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="form.is_active" /> Active</label>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="Boolean(openTarget)" max-width="sm" @close="openTarget = null">
            <form class="space-y-4 p-6" @submit.prevent="submitOpen">
                <h2 class="text-lg font-semibold text-brand-navy">Open session — {{ openTarget?.name }}</h2>
                <div>
                    <InputLabel value="Opening cash" />
                    <TextInput v-model="openForm.opening_cash" type="number" min="0" step="any" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="openTarget = null">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="openForm.processing">Open</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="Boolean(closeTarget)" max-width="sm" @close="closeTarget = null">
            <form class="space-y-4 p-6" @submit.prevent="submitClose">
                <h2 class="text-lg font-semibold text-brand-navy">Close session — {{ closeTarget?.name }}</h2>
                <div>
                    <InputLabel value="Closing cash counted" />
                    <TextInput v-model="closeForm.closing_cash" type="number" min="0" step="any" class="mt-1 block w-full" required />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="closeTarget = null">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="closeForm.processing">Close</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
