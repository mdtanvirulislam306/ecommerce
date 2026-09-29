<script setup>
import RolePicker from '../../Components/RolePicker.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Dropdown from '@/Components/Dropdown.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, required: true },
    roleOptions: { type: Array, default: () => [] },
    invitationValidDays: { type: Number, default: 7 },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');

const statusTabs = computed(() => [
    { value: null, label: 'Everyone', count: props.summary.total },
    { value: 'active', label: 'Active', count: props.summary.active },
    { value: 'invited', label: 'Invited', count: props.summary.invited },
    { value: 'deactivated', label: 'Deactivated', count: props.summary.deactivated },
]);

const applyFilters = (overrides = {}) => {
    router.get(
        route('settings.users.index'),
        {
            search: search.value || undefined,
            status: props.filters.status || undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 300);
});

const setStatus = (value) => applyFilters({ status: value || undefined });

const initials = (name) =>
    name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

const avatarTones = ['bg-brand-navy', 'bg-brand-teal-dark', 'bg-brand-orange', 'bg-indigo-500', 'bg-rose-500', 'bg-emerald-600'];
const avatarTone = (row) => (row.status === 'deactivated' ? 'bg-gray-300' : avatarTones[row.id % avatarTones.length]);

const statusPill = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    invited: 'bg-amber-50 text-amber-700 ring-amber-200',
    deactivated: 'bg-gray-100 text-gray-500 ring-gray-200',
};

const relativeTime = (iso) => {
    if (!iso) {
        return null;
    }
    const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000);
    const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return formatter.format(Math.round(seconds / size), unit);
        }
    }
    return 'just now';
};

const invitationExpired = (row) =>
    row.invited_at && Date.now() - new Date(row.invited_at).getTime() > props.invitationValidDays * 86400000;

const activityLine = (row) => {
    if (row.status === 'invited') {
        const sent = `Invited ${relativeTime(row.invited_at)}${row.invited_by ? ` by ${row.invited_by}` : ''}`;
        return invitationExpired(row) ? `${sent} · link expired` : sent;
    }
    if (row.status === 'deactivated') {
        return `Deactivated ${relativeTime(row.deactivated_at)}`;
    }
    return row.last_login_at ? `Last active ${relativeTime(row.last_login_at)}` : 'Has not signed in yet';
};

const canManage = (row) => !row.is_owner && !row.is_self;

const inviteOpen = ref(false);
const inviteForm = useForm({ name: '', email: '', role_ids: [] });

const openInvite = () => {
    inviteForm.reset();
    inviteForm.clearErrors();
    inviteOpen.value = true;
};

const submitInvite = () => {
    inviteForm.post(route('settings.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            inviteOpen.value = false;
            inviteForm.reset();
        },
    });
};

const rolesTarget = ref(null);
const rolesForm = useForm({ role_ids: [] });

const openRoles = (row) => {
    rolesTarget.value = row;
    rolesForm.role_ids = row.roles.map((role) => role.id);
    rolesForm.clearErrors();
};

const submitRoles = () => {
    rolesForm.put(route('settings.users.assign-roles', rolesTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { rolesTarget.value = null; },
    });
};

const confirmTarget = ref(null);
const confirmAction = ref(null);
const actionForm = useForm({});

const confirmCopy = computed(() => {
    const name = confirmTarget.value?.name;
    return {
        deactivate: {
            title: `Deactivate ${name}?`,
            body: 'They are signed out straight away and can no longer sign in. Their orders, sales and notes stay in your records. You can reactivate them any time.',
            button: 'Deactivate',
            danger: true,
        },
        cancel: {
            title: `Cancel the invitation for ${name}?`,
            body: 'The link in their email stops working. You can invite them again later.',
            button: 'Cancel invitation',
            danger: true,
        },
    }[confirmAction.value] ?? null;
});

const askConfirm = (row, action) => {
    confirmTarget.value = row;
    confirmAction.value = action;
};

const closeConfirm = () => {
    confirmTarget.value = null;
    confirmAction.value = null;
};

const runConfirmed = () => {
    const row = confirmTarget.value;
    const options = { preserveScroll: true, onSuccess: closeConfirm };

    if (confirmAction.value === 'deactivate') {
        actionForm.post(route('settings.users.deactivate', row.id), options);
    } else if (confirmAction.value === 'cancel') {
        actionForm.delete(route('settings.users.destroy', row.id), options);
    }
};

const reactivate = (row) => actionForm.post(route('settings.users.reactivate', row.id), { preserveScroll: true });
const resendInvite = (row) => actionForm.post(route('settings.users.resend-invitation', row.id), { preserveScroll: true });
</script>

<template>
    <Head title="Team" />

    <AdminLayout title="Team">
        <div v-if="flash?.success" class="mb-4 flex items-center gap-2 rounded-xl bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy ring-1 ring-brand-teal/20">
            <svg class="h-5 w-5 shrink-0 text-brand-teal-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ flash.success }}
        </div>

        <div class="max-w-6xl space-y-6">
            <section class="flex flex-col gap-4 rounded-2xl bg-brand-navy p-6 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Your team</h2>
                    <p class="mt-1 max-w-xl text-sm text-white/70">
                        Invite the people who help run your shop. Each person only sees what their roles allow, and you can turn off access in one click.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-black/10 transition hover:bg-brand-orange-dark"
                    @click="openInvite"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                    Invite staff
                </button>
            </section>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <nav class="flex gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1" aria-label="Filter by status">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.label"
                        type="button"
                        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition"
                        :class="(filters.status ?? null) === tab.value ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500 hover:text-brand-navy'"
                        @click="setStatus(tab.value)"
                    >
                        {{ tab.label }}
                        <span
                            class="rounded-full px-1.5 text-xs font-semibold"
                            :class="(filters.status ?? null) === tab.value ? 'bg-brand-navy text-white' : 'bg-gray-200 text-gray-600'"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </nav>
                <label class="relative block lg:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </span>
                    <TextInput v-model="search" type="search" class="w-full pl-9" placeholder="Search by name or email…" />
                </label>
            </div>

            <section class="admin-card !p-0">
                <div v-if="!users.data.length" class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-teal/10 text-brand-teal-dark">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-brand-navy">No one matches</p>
                    <p class="mt-1 text-sm text-gray-500">Try another search or filter.</p>
                </div>

                <ul v-else class="divide-y divide-gray-100">
                    <li
                        v-for="row in users.data"
                        :key="row.id"
                        class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center"
                        :class="row.status === 'deactivated' ? 'bg-gray-50/60' : ''"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                :class="avatarTone(row)"
                            >
                                {{ initials(row.name) }}
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="truncate font-semibold" :class="row.status === 'deactivated' ? 'text-gray-500' : 'text-brand-navy'">{{ row.name }}</p>
                                    <span v-if="row.is_self" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">You</span>
                                </div>
                                <p class="truncate text-sm text-gray-500">{{ row.email }}</p>
                                <p class="mt-0.5 text-xs" :class="invitationExpired(row) && row.status === 'invited' ? 'text-red-600' : 'text-gray-400'">
                                    {{ activityLine(row) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 sm:w-64 sm:justify-start">
                            <span v-if="row.is_owner" class="inline-flex items-center gap-1 rounded-full bg-brand-orange/10 px-2.5 py-1 text-xs font-semibold text-brand-orange">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 7l4.5 4L12 4l4.5 7L21 7l-2 12H5L3 7z" />
                                </svg>
                                Owner · full access
                            </span>
                            <template v-else-if="row.roles.length">
                                <span v-for="role in row.roles" :key="role.id" class="rounded-full bg-brand-teal/10 px-2.5 py-1 text-xs font-medium text-brand-navy">
                                    {{ role.name }}
                                </span>
                            </template>
                            <span v-else class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">No role · can't open anything</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 sm:w-52 sm:justify-end">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1" :class="statusPill[row.status]">
                                {{ row.status_label }}
                            </span>

                            <div v-if="canManage(row)" class="flex items-center gap-1">
                                <button
                                    v-if="row.status !== 'deactivated'"
                                    type="button"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-navy ring-1 ring-gray-200 transition hover:bg-gray-50"
                                    @click="openRoles(row)"
                                >
                                    Roles
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-lg bg-brand-teal/10 px-3 py-1.5 text-xs font-semibold text-brand-teal-dark transition hover:bg-brand-teal/20"
                                    :disabled="actionForm.processing"
                                    @click="reactivate(row)"
                                >
                                    Reactivate
                                </button>

                                <Dropdown v-if="row.status !== 'deactivated'" align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-brand-navy"
                                            :aria-label="`More actions for ${row.name}`"
                                        >
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 7a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <template v-if="row.status === 'invited'">
                                            <button type="button" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50" @click="resendInvite(row)">
                                                Resend invitation
                                            </button>
                                            <button type="button" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50" @click="askConfirm(row, 'cancel')">
                                                Cancel invitation
                                            </button>
                                        </template>
                                        <button v-else type="button" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50" @click="askConfirm(row, 'deactivate')">
                                            Deactivate
                                        </button>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </li>
                </ul>

                <div v-if="users.last_page > 1" class="border-t border-gray-100 px-5 py-3">
                    <TablePagination :paginator="users" />
                </div>
            </section>
        </div>

        <Modal :show="inviteOpen" max-width="2xl" @close="inviteOpen = false">
            <form class="p-6 sm:p-8" @submit.prevent="submitInvite">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-orange/10 text-brand-orange">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-brand-navy">Invite a team member</h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            They get an email with a link to choose their password. The link works for {{ invitationValidDays }} days.
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="invite_name" value="Full name" />
                        <TextInput id="invite_name" v-model="inviteForm.name" class="mt-1 block w-full" required autofocus placeholder="e.g. Karim Hossain" />
                        <InputError class="mt-1" :message="inviteForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="invite_email" value="Work email" />
                        <TextInput id="invite_email" v-model="inviteForm.email" type="email" class="mt-1 block w-full" required placeholder="karim@example.com" />
                        <InputError class="mt-1" :message="inviteForm.errors.email" />
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-gray-700">What can they do?</p>
                    <p class="mb-3 text-xs text-gray-500">Pick one or more roles. You can change this later.</p>
                    <RolePicker v-model="inviteForm.role_ids" :roles="roleOptions" />
                    <InputError class="mt-2" :message="inviteForm.errors.role_ids || inviteForm.errors['role_ids.0']" />
                </div>

                <div class="mt-8 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <SecondaryButton type="button" class="justify-center" @click="inviteOpen = false">Cancel</SecondaryButton>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-orange px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-orange-dark disabled:opacity-60"
                        :disabled="inviteForm.processing || !roleOptions.length"
                    >
                        Send invitation
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="!!rolesTarget" max-width="2xl" @close="rolesTarget = null">
            <form class="p-6 sm:p-8" @submit.prevent="submitRoles">
                <h2 class="text-lg font-semibold text-brand-navy">Roles for {{ rolesTarget?.name }}</h2>
                <p class="mt-0.5 text-sm text-gray-500">Changes apply the next time they open a page.</p>
                <div class="mt-5">
                    <RolePicker v-model="rolesForm.role_ids" :roles="roleOptions" />
                </div>
                <p v-if="!rolesForm.role_ids.length && roleOptions.length" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    With no role they can sign in but won't be able to open any page.
                </p>
                <InputError class="mt-2" :message="rolesForm.errors.role_ids || rolesForm.errors['role_ids.0']" />
                <div class="mt-8 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="rolesTarget = null">Cancel</SecondaryButton>
                    <button
                        type="submit"
                        class="rounded-lg bg-brand-navy px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-navy-dark disabled:opacity-60"
                        :disabled="rolesForm.processing"
                    >
                        Save roles
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="!!confirmCopy" max-width="md" @close="closeConfirm">
            <div class="p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">{{ confirmCopy?.title }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ confirmCopy?.body }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="closeConfirm">Keep</SecondaryButton>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="actionForm.processing"
                        @click="runConfirmed"
                    >
                        {{ confirmCopy?.button }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
