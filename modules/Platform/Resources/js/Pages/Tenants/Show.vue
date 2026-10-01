<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    team: { type: Array, default: () => [] },
    accessHistory: { type: Array, default: () => [] },
});

const page = usePage();
const impersonationError = computed(() => page.props.errors?.impersonation);

const suspend = () => router.post(route('platform.tenants.suspend', props.tenant.id), {}, { preserveScroll: true });
const activate = () => router.post(route('platform.tenants.activate', props.tenant.id), {}, { preserveScroll: true });

const isSuspended = computed(() => props.tenant.status === 'suspended');
const planName = computed(() => (props.tenant.plans ?? []).find((plan) => plan.id === props.tenant.plan_id)?.name ?? null);

const target = ref(null);
const starting = ref(false);

const startImpersonation = () => {
    starting.value = true;
    router.post(route('platform.tenants.impersonate', [props.tenant.id, target.value.id]), {}, {
        preserveScroll: true,
        onFinish: () => {
            starting.value = false;
            target.value = null;
        },
    });
};

const initials = (name) =>
    name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

const statusPill = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    invited: 'bg-amber-50 text-amber-700 ring-amber-200',
    deactivated: 'bg-gray-100 text-gray-500 ring-gray-200',
};

const formatWhen = (iso) =>
    iso
        ? new Date(iso).toLocaleString('en-BD', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })
        : 'Never';
</script>

<template>
    <PlatformShell :title="tenant.name" full-width>
        <div v-if="impersonationError" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">
            {{ impersonationError }}
        </div>

        <div class="space-y-6">
            <Link :href="route('platform.tenants.index')" class="inline-flex items-center gap-1 text-sm font-medium text-brand-navy hover:text-brand-orange">
                ← All shops
            </Link>

            <section class="flex flex-col gap-5 rounded-2xl bg-brand-navy p-6 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lg font-bold ring-1 ring-white/20">
                        {{ initials(tenant.name) }}
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-semibold">{{ tenant.name }}</h1>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                                :class="isSuspended ? 'bg-red-500/20 text-red-100 ring-1 ring-red-300/40' : 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/40'"
                            >
                                {{ tenant.status }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-white/70">{{ tenant.domain || 'No domain yet' }} · {{ tenant.slug }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('platform.tenants.edit', tenant.id)"
                        class="rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20"
                    >
                        Edit shop
                    </Link>
                    <button
                        v-if="isSuspended"
                        type="button"
                        class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600"
                        @click="activate"
                    >
                        Activate
                    </button>
                    <button
                        v-else
                        type="button"
                        class="rounded-lg bg-red-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-600"
                        @click="suspend"
                    >
                        Suspend
                    </button>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <section class="admin-card !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-brand-navy">Team</h2>
                            <p class="text-xs text-gray-500">Sign in as anyone here to see the shop exactly as they do.</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ team.length }}</span>
                    </div>

                    <p v-if="!team.length" class="px-5 py-10 text-center text-sm text-gray-500">This shop has no users yet.</p>

                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="member in team" :key="member.id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                    :class="member.status === 'deactivated' ? 'bg-gray-300' : member.is_owner ? 'bg-brand-orange' : 'bg-brand-navy'"
                                >
                                    {{ initials(member.name) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="truncate font-semibold text-brand-navy">{{ member.name }}</p>
                                        <span v-if="member.is_owner" class="rounded-full bg-brand-orange/10 px-2 py-0.5 text-[11px] font-semibold text-brand-orange">Owner</span>
                                        <span v-for="role in member.roles" :key="role" class="rounded-full bg-brand-teal/10 px-2 py-0.5 text-[11px] font-medium text-brand-navy">{{ role }}</span>
                                    </div>
                                    <p class="truncate text-sm text-gray-500">{{ member.email }}</p>
                                    <p class="text-xs text-gray-400">Last active: {{ formatWhen(member.last_login_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1" :class="statusPill[member.status]">{{ member.status_label }}</span>
                                <button
                                    v-if="member.can_impersonate"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-orange-dark disabled:opacity-50"
                                    :disabled="isSuspended"
                                    :title="isSuspended ? 'Activate the shop first' : `Sign in as ${member.name}`"
                                    @click="target = member"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25" />
                                    </svg>
                                    Log in as
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>

                <aside class="space-y-6">
                    <section class="admin-card space-y-3 text-sm">
                        <h2 class="text-sm font-semibold text-brand-navy">Plan & billing</h2>
                        <dl class="space-y-2">
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Plan</dt><dd class="font-medium text-brand-navy">{{ planName || '—' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Ends</dt><dd class="font-medium text-brand-navy">{{ tenant.ends_at || '—' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Owner</dt><dd class="truncate font-medium text-brand-navy">{{ tenant.owner?.email || '—' }}</dd></div>
                        </dl>
                        <p v-if="tenant.payment_note" class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ tenant.payment_note }}</p>
                        <p v-if="tenant.notes" class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ tenant.notes }}</p>
                    </section>

                    <section class="admin-card">
                        <h2 class="text-sm font-semibold text-brand-navy">Access history</h2>
                        <p class="text-xs text-gray-500">Every platform sign-in, also visible to the shop owner.</p>
                        <p v-if="!accessHistory.length" class="mt-4 text-sm text-gray-400">No one from the platform has signed in here.</p>
                        <ol v-else class="mt-4 space-y-3">
                            <li v-for="entry in accessHistory" :key="entry.id" class="flex gap-3">
                                <span
                                    class="mt-1 h-2 w-2 shrink-0 rounded-full"
                                    :class="entry.action === 'impersonation.started' ? 'bg-brand-orange' : 'bg-gray-300'"
                                />
                                <div class="min-w-0 text-xs">
                                    <p class="text-brand-navy">
                                        <span class="font-semibold">{{ entry.admin }}</span>
                                        {{ entry.action === 'impersonation.started' ? 'signed in as' : 'left' }}
                                        <span class="font-semibold">{{ entry.user }}</span>
                                        <span v-if="entry.minutes !== null" class="text-gray-500"> after {{ entry.minutes }} min</span>
                                    </p>
                                    <p class="text-gray-400">{{ formatWhen(entry.created_at) }}<span v-if="entry.ip_address"> · {{ entry.ip_address }}</span></p>
                                </div>
                            </li>
                        </ol>
                    </section>
                </aside>
            </div>
        </div>

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
                            You'll open {{ tenant.name }} with exactly their access. This is recorded in the shop's audit log,
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
