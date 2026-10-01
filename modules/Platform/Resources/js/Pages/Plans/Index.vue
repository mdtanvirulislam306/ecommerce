<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatMoney } from '@/utils/formatMoney';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
});

const page = usePage();
const planError = computed(() => page.props.errors?.plan);

const moduleNames = computed(() => Object.fromEntries(props.modules.map((mod) => [mod.code, mod.name])));
const coreCount = computed(() => props.modules.filter((mod) => mod.is_core).length);
const optionalCodes = computed(() => new Set(props.modules.filter((mod) => !mod.is_core).map((mod) => mod.code)));

const includedModules = (plan) => plan.module_codes.filter((code) => optionalCodes.value.has(code));

const price = (plan) => (plan.price_monthly ? formatMoney(plan.price_monthly / 100, plan.currency, { decimals: plan.price_monthly % 100 ? 2 : 0 }) : 'Free');

const busy = ref(false);
const makeDefault = (plan) => {
    busy.value = true;
    router.post(route('platform.plans.make-default', plan.id), {}, { preserveScroll: true, onFinish: () => (busy.value = false) });
};

const deleting = ref(null);
const confirmDelete = () => {
    busy.value = true;
    router.delete(route('platform.plans.destroy', deleting.value.id), {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <PlatformShell title="Plans" full-width>
        <div v-if="planError" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">{{ planError }}</div>

        <section class="flex flex-col gap-4 rounded-2xl bg-brand-navy p-6 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-lg font-semibold">Plans</h1>
                <p class="mt-1 max-w-xl text-sm text-white/70">
                    Each plan sets a monthly price and which modules a shop can open. The default plan is where every new shop starts.
                </p>
            </div>
            <Link
                :href="route('platform.plans.create')"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-black/10 transition hover:bg-brand-orange-dark"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New plan
            </Link>
        </section>

        <div v-if="!plans.length" class="admin-card py-14 text-center">
            <p class="text-sm font-semibold text-brand-navy">No plans yet</p>
            <p class="mt-1 text-sm text-gray-500">Create your first plan to start provisioning shops.</p>
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            <article
                v-for="plan in plans"
                :key="plan.id"
                class="flex flex-col rounded-2xl border bg-white shadow-sm transition"
                :class="[plan.is_default ? 'border-brand-orange/60 ring-1 ring-brand-orange/30' : 'border-gray-100', !plan.is_active ? 'opacity-75' : '']"
            >
                <div class="flex-1 p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-brand-navy">{{ plan.name }}</h2>
                            <p class="font-mono text-xs text-gray-400">{{ plan.code }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap justify-end gap-1">
                            <span v-if="plan.is_default" class="rounded-full bg-brand-orange/10 px-2.5 py-1 text-[11px] font-semibold text-brand-orange">Default</span>
                            <span v-if="!plan.is_active" class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">Hidden</span>
                        </div>
                    </div>

                    <p class="mt-4 text-3xl font-semibold text-brand-navy">
                        {{ price(plan) }}<span v-if="plan.price_monthly" class="text-sm font-normal text-gray-400"> / month</span>
                    </p>
                    <p v-if="plan.description" class="mt-2 text-sm text-gray-500">{{ plan.description }}</p>

                    <dl class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                            <dt class="text-xs text-gray-500">Shops on it</dt>
                            <dd class="text-lg font-semibold text-brand-navy">{{ plan.shops }}</dd>
                        </div>
                        <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                            <dt class="text-xs text-gray-500">Earns monthly</dt>
                            <dd class="text-lg font-semibold text-brand-navy">{{ formatMoney((plan.shops * plan.price_monthly) / 100, plan.currency, { decimals: 0 }) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            {{ includedModules(plan).length + coreCount }} modules
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span
                                v-for="code in includedModules(plan).slice(0, 8)"
                                :key="code"
                                class="rounded-full bg-brand-teal/10 px-2.5 py-1 text-xs font-medium text-brand-navy"
                            >
                                {{ moduleNames[code] ?? code }}
                            </span>
                            <span v-if="includedModules(plan).length > 8" class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">
                                +{{ includedModules(plan).length - 8 }} more
                            </span>
                            <span v-if="!includedModules(plan).length" class="text-xs text-gray-400">Core modules only</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 border-t border-gray-100 px-5 py-3">
                    <Link
                        :href="route('platform.plans.edit', plan.id)"
                        class="rounded-lg bg-brand-navy px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-navy-dark"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="!plan.is_default"
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-navy ring-1 ring-gray-200 transition hover:bg-gray-50 disabled:opacity-50"
                        :disabled="busy"
                        @click="makeDefault(plan)"
                    >
                        Make default
                    </button>
                    <button
                        v-if="plan.can_delete"
                        type="button"
                        class="ml-auto rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                        @click="deleting = plan"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>

        <Modal :show="!!deleting" max-width="md" @close="deleting = null">
            <div class="p-6">
                <h2 class="text-base font-semibold text-brand-navy">Delete the {{ deleting?.name }} plan?</h2>
                <p class="mt-1 text-sm text-gray-500">No shop has ever been billed on it, so nothing else changes. This can't be undone.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="deleting = null">Cancel</SecondaryButton>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="busy"
                        @click="confirmDelete"
                    >
                        Delete plan
                    </button>
                </div>
            </div>
        </Modal>
    </PlatformShell>
</template>
