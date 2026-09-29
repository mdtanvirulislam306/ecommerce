<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tenants: { type: Array, default: () => [] },
});

const search = ref('');
const status = ref(null);

const statusTabs = computed(() => [
    { value: null, label: 'All shops', count: props.tenants.length },
    { value: 'active', label: 'Active', count: props.tenants.filter((tenant) => tenant.status === 'active').length },
    { value: 'trial', label: 'Trial', count: props.tenants.filter((tenant) => tenant.status === 'trial').length },
    { value: 'suspended', label: 'Suspended', count: props.tenants.filter((tenant) => tenant.status === 'suspended').length },
]);

const visibleTenants = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.tenants.filter((tenant) => {
        if (status.value && tenant.status !== status.value) {
            return false;
        }
        if (!term) {
            return true;
        }
        return [tenant.name, tenant.slug, tenant.domain, tenant.owner_name, tenant.owner_email]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(term));
    });
});

const statusPill = (value) =>
    value === 'suspended' ? 'bg-red-50 text-red-700 ring-red-200' : value === 'trial' ? 'bg-brand-teal/10 text-brand-navy ring-brand-teal/30' : 'bg-emerald-50 text-emerald-700 ring-emerald-200';

const initials = (name) =>
    name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
</script>

<template>
    <PlatformShell title="Shops">
        <section class="flex flex-col gap-4 rounded-2xl bg-brand-navy p-6 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-lg font-semibold">Shops</h1>
                <p class="mt-1 max-w-xl text-sm text-white/70">Every shop on the platform, with its domain, plan and owner. Open one to manage its plan, team and access.</p>
            </div>
            <Link
                :href="route('platform.tenants.create')"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-black/10 transition hover:bg-brand-orange-dark"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New shop
            </Link>
        </section>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <nav class="flex gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1" aria-label="Filter by status">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.label"
                    type="button"
                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition"
                    :class="status === tab.value ? 'bg-white text-brand-navy shadow-sm' : 'text-gray-500 hover:text-brand-navy'"
                    @click="status = tab.value"
                >
                    {{ tab.label }}
                    <span class="rounded-full px-1.5 text-xs font-semibold" :class="status === tab.value ? 'bg-brand-navy text-white' : 'bg-gray-200 text-gray-600'">
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
            <label class="relative block lg:w-80">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </span>
                <TextInput v-model="search" type="search" class="w-full pl-9" placeholder="Search shop, domain or owner…" />
            </label>
        </div>

        <section class="admin-card !p-0">
            <div v-if="!visibleTenants.length" class="px-6 py-14 text-center">
                <p class="text-sm font-semibold text-brand-navy">{{ tenants.length ? 'No shops match' : 'No shops yet' }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ tenants.length ? 'Try another search or status.' : 'Create the first shop to get started.' }}</p>
            </div>

            <ul v-else class="divide-y divide-gray-100">
                <li v-for="tenant in visibleTenants" :key="tenant.id">
                    <Link :href="route('platform.tenants.show', tenant.id)" class="flex flex-col gap-3 px-5 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                                :class="tenant.status === 'suspended' ? 'bg-gray-100 text-gray-400' : 'bg-brand-navy/10 text-brand-navy'"
                            >
                                {{ initials(tenant.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-brand-navy">{{ tenant.name }}</p>
                                <p class="truncate text-sm text-gray-500">{{ tenant.domain || 'No domain yet' }}</p>
                            </div>
                        </div>
                        <div class="min-w-0 sm:w-56">
                            <p class="truncate text-sm text-brand-navy">{{ tenant.owner_name || '—' }}</p>
                            <p class="truncate text-xs text-gray-400">{{ tenant.owner_email }}</p>
                        </div>
                        <div class="sm:w-32">
                            <p class="text-sm font-medium text-brand-navy">{{ tenant.plan_name || 'No plan' }}</p>
                            <p class="text-xs text-gray-400">Since {{ formatDate(tenant.created_at) }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:w-28 sm:justify-end">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1" :class="statusPill(tenant.status)">{{ tenant.status }}</span>
                            <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>
    </PlatformShell>
</template>
