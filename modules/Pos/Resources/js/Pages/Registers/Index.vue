<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ActionIcon from '@/Components/Admin/ActionIcon.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
const statusFilter = ref('all');
const search = ref('');

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

const filteredRegisters = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.registers.filter((register) => {
        if (statusFilter.value === 'open' && !register.has_open_session) {
            return false;
        }
        if (statusFilter.value === 'closed' && register.has_open_session) {
            return false;
        }
        if (statusFilter.value === 'active' && !register.is_active) {
            return false;
        }
        if (!q) {
            return true;
        }

        return `${register.name} ${register.code}`.toLowerCase().includes(q);
    });
});

const stats = computed(() => ({
    total: props.registers.length,
    open: props.registers.filter((r) => r.has_open_session).length,
    active: props.registers.filter((r) => r.is_active).length,
}));

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

        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <div class="admin-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Registers</p>
                <p class="mt-1 text-2xl font-semibold text-brand-navy">{{ stats.total }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Open sessions</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ stats.open }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active</p>
                <p class="mt-1 text-2xl font-semibold text-brand-navy">{{ stats.active }}</p>
            </div>
        </div>

        <div class="admin-data-table">
            <div class="admin-data-table__toolbar">
                <div>
                    <h2 class="text-sm font-semibold text-brand-navy">Registers</h2>
                    <p class="text-xs text-gray-500">{{ filteredRegisters.length }} showing</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name or code…"
                        class="admin-data-table__search"
                    />
                    <Link
                        :href="route('pos.terminal')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-navy px-3 py-2 text-xs font-semibold text-white hover:bg-brand-navy/90"
                    >
                        <ActionIcon name="terminal" />
                        Open Terminal
                    </Link>
                    <PrimaryButton type="button" @click="openCreate">Add register</PrimaryButton>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 border-b border-gray-100 px-4 py-2.5 sm:px-5">
                <button
                    v-for="opt in [
                        { value: 'all', label: 'All' },
                        { value: 'open', label: 'Session open' },
                        { value: 'closed', label: 'Session closed' },
                        { value: 'active', label: 'Active only' },
                    ]"
                    :key="opt.value"
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[11px] font-medium transition ring-1"
                    :class="
                        statusFilter === opt.value
                            ? 'bg-brand-navy text-white ring-brand-navy'
                            : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300'
                    "
                    @click="statusFilter = opt.value"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200 bg-gray-50/90">
                        <tr class="admin-data-table__head">
                            <th>Register</th>
                            <th>Code</th>
                            <th>Session</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="register in filteredRegisters" :key="register.id" class="admin-data-table__row">
                            <td class="admin-data-table__cell">
                                <p class="font-medium text-brand-navy">{{ register.name }}</p>
                            </td>
                            <td class="admin-data-table__cell">
                                <span class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600">{{ register.code }}</span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                                    :class="
                                        register.has_open_session
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : 'bg-gray-100 text-gray-600 ring-gray-200'
                                    "
                                >
                                    {{ register.has_open_session ? 'Open' : 'Closed' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1"
                                    :class="
                                        register.is_active
                                            ? 'bg-sky-50 text-sky-800 ring-sky-200'
                                            : 'bg-red-50 text-red-700 ring-red-200'
                                    "
                                >
                                    {{ register.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="admin-data-table__cell text-gray-600">{{ register.is_default ? 'Yes' : '—' }}</td>
                            <td class="admin-data-table__cell">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button type="button" class="admin-data-table__action" title="Edit" @click="openEdit(register)">
                                        <ActionIcon name="edit" />
                                    </button>
                                    <button
                                        v-if="!register.has_open_session"
                                        type="button"
                                        class="admin-data-table__action text-emerald-700"
                                        title="Open session"
                                        @click="openTarget = register"
                                    >
                                        <ActionIcon name="open" />
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="admin-data-table__action text-amber-700"
                                        title="Close session"
                                        @click="closeTarget = register"
                                    >
                                        <ActionIcon name="close" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!filteredRegisters.length" class="p-4">
                <AdminEmptyState
                    title="No registers match"
                    description="Try another filter, or add a new register."
                    action-label="Add register"
                    @action="openCreate"
                />
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
