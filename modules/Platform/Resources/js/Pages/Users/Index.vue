<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, required: true },
    shops: { type: Array, default: () => [] },
});

const page = usePage();
const impersonationError = computed(() => page.props.errors?.impersonation);

const search = ref(props.filters.search ?? '');
const shopId = ref(props.filters.tenant_id ?? '');
const status = ref(props.filters.status ?? '');

const typeTabs = computed(() => [
    { value: null, label: 'Everyone', count: props.summary.total },
    { value: 'owner', label: 'Shop owners', count: props.summary.owners },
    { value: 'staff', label: 'Staff', count: props.summary.staff },
    { value: 'platform_admin', label: 'Platform admins', count: props.summary.platform_admins },
]);

const applyFilters = (overrides = {}) => {
    router.get(
        route('platform.users.index'),
        {
            search: search.value || undefined,
            tenant_id: shopId.value || undefined,
            status: status.value || undefined,
            type: props.filters.type || undefined,
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
watch([shopId, status], () => applyFilters());

const setType = (value) => applyFilters({ type: value || undefined });

const hasFilters = computed(() => Boolean(props.filters.search || props.filters.tenant_id || props.filters.status || props.filters.type));

const clearFilters = () => {
    search.value = '';
    shopId.value = '';
    status.value = '';
    router.get(route('platform.users.index'), {}, { preserveScroll: true, replace: true });
};

const initials = (name) =>
    name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

const avatarTone = (row) => {
    if (row.status === 'deactivated') {
        return 'bg-gray-300';
    }
    return { platform_admin: 'bg-indigo-600', owner: 'bg-brand-orange', staff: 'bg-brand-navy' }[row.type];
};

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

const target = ref(null);
const starting = ref(false);

const shopSuspended = (row) => row.shop?.status === 'suspended';

const startImpersonation = () => {
    starting.value = true;
    router.post(route('platform.tenants.impersonate', [target.value.shop.id, target.value.id]), {}, {
        preserveScroll: true,
        onFinish: () => {
            starting.value = false;
            target.value = null;
        },
    });
};
</script>

<template>
    <PlatformShell title="Users">
        <div v-if="impersonationError" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
            {{ impersonationError }}
        </div>

        <section class="rounded-2xl bg-brand-navy p-6 text-white shadow-sm">
            <h1 class="text-lg font-semibold">Everyone on the platform</h1>
            <p class="mt-1 max-w-2xl text-sm text-white/70">
                Find any shop owner or staff member across every shop. Sign in as them to see exactly what they see — each visit is recorded in that shop's audit log.
            </p>
        </section>

        <div class="flex flex-col gap-3">
            <nav class="flex gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1" aria-label="Filter by type">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.label"
                    type="button"
                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition"
                    :class="(filters.type ?? null) === tab.value ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500 hover:text-brand-navy'"
                    @click="setType(tab.value)"
                >
                    {{ tab.label }}
                    <span
                        class="rounded-full px-1.5 text-xs font-semibold"
                        :class="(filters.type ?? null) === tab.value ? 'bg-brand-navy text-white' : 'bg-gray-200 text-gray-600'"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>

            <div class="grid gap-3 sm:grid-cols-[1fr_14rem_12rem_auto]">
                <label class="relative block">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </span>
                    <TextInput v-model="search" type="search" class="w-full pl-9" placeholder="Search by name or email…" />
                </label>
                <select v-model="shopId" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal" aria-label="Shop">
                    <option value="">All shops</option>
                    <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                </select>
                <select v-model="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal" aria-label="Status">
                    <option value="">Any status</option>
                    <option value="active">Active</option>
                    <option value="invited">Invitation sent</option>
                    <option value="deactivated">Deactivated</option>
                </select>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-500 transition hover:bg-gray-100 hover:text-brand-navy"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>
        </div>

        <section class="admin-card !p-0">
            <div v-if="!users.data.length" class="px-6 py-14 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-teal/10 text-brand-teal-dark">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-brand-navy">No one matches</p>
                <p class="mt-1 text-sm text-gray-500">Try another search, shop or status.</p>
            </div>

            <ul v-else class="divide-y divide-gray-100">
                <li
                    v-for="row in users.data"
                    :key="row.id"
                    class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center"
                    :class="row.status === 'deactivated' ? 'bg-gray-50/60' : ''"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" :class="avatarTone(row)">
                            {{ initials(row.name) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold" :class="row.status === 'deactivated' ? 'text-gray-500' : 'text-brand-navy'">{{ row.name }}</p>
                            <p class="truncate text-sm text-gray-500">{{ row.email }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ row.last_login_at ? `Last active ${relativeTime(row.last_login_at)}` : 'Has not signed in yet' }}
                            </p>
                        </div>
                    </div>

                    <div class="min-w-0 lg:w-56">
                        <template v-if="row.shop">
                            <Link :href="route('platform.tenants.show', row.shop.id)" class="inline-flex max-w-full items-center gap-1.5 text-sm font-semibold text-brand-navy hover:text-brand-orange">
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" />
                                </svg>
                                <span class="truncate">{{ row.shop.name }}</span>
                            </Link>
                            <p v-if="shopSuspended(row)" class="text-xs font-medium text-red-600">Shop suspended</p>
                        </template>
                        <p v-else class="text-sm text-gray-400">No shop</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span v-if="row.type === 'platform_admin'" class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">Platform admin</span>
                            <span v-else-if="row.type === 'owner'" class="rounded-full bg-brand-orange/10 px-2 py-0.5 text-[11px] font-semibold text-brand-orange">Owner</span>
                            <span v-for="role in row.roles" :key="role" class="rounded-full bg-brand-teal/10 px-2 py-0.5 text-[11px] font-medium text-brand-navy">{{ role }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 lg:w-56 lg:justify-end">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1" :class="statusPill[row.status]">{{ row.status_label }}</span>
                        <button
                            v-if="row.can_impersonate"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-orange-dark disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="shopSuspended(row)"
                            :title="shopSuspended(row) ? 'Activate the shop first' : `Sign in as ${row.name}`"
                            @click="target = row"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25" />
                            </svg>
                            Log in as
                        </button>
                    </div>
                </li>
            </ul>

            <div v-if="users.last_page > 1" class="flex flex-col gap-2 border-t border-gray-100 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500">Showing {{ users.from }}–{{ users.to }} of {{ users.total }}</p>
                <TablePagination :paginator="users" />
            </div>
        </section>

        <Modal :show="!!target" max-width="md" @close="target = null">
            <div class="p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-orange/10 text-brand-orange">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Sign in as {{ target?.name }}?</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            You'll open {{ target?.shop?.name }} with exactly their access. This is recorded in the shop's audit log,
                            and a banner lets you return to the platform at any time.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="target = null">Cancel</SecondaryButton>
                    <button
                        type="button"
                        class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-orange-dark disabled:opacity-60"
                        :disabled="starting"
                        @click="startImpersonation"
                    >
                        Continue as {{ target?.name.split(' ')[0] }}
                    </button>
                </div>
            </div>
        </Modal>
    </PlatformShell>
</template>
