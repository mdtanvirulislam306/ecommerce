<script setup>
import { formatMoney } from '@/utils/formatMoney';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    plans: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    subscription: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const shopName = computed(() => page.props.tenant?.name ?? 'This shop');

const assignForm = useForm({});

const assign = (planId) => {
    assignForm.post(route('billing.plans.assign', planId), { preserveScroll: true });
};

const price = (plan) => (plan.price_monthly ? `${formatMoney(plan.price_monthly / 100, plan.currency, { decimals: 0 })} / month` : 'Free');
</script>

<template>
    <div class="max-w-5xl space-y-6">
        <div v-if="flash?.success" class="rounded-xl bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy ring-1 ring-brand-teal/20">
            {{ flash.success }}
        </div>

        <section class="flex flex-col gap-4 rounded-2xl bg-brand-navy p-6 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-white/50">{{ shopName }}</p>
                <h2 class="mt-1 text-lg font-semibold">On {{ subscription?.plan_name || 'no plan' }}</h2>
                <p class="mt-1 max-w-xl text-sm text-white/70">
                    Switch this shop's plan below. Prices and modules for every plan are edited in the platform console.
                </p>
            </div>
            <Link
                :href="route('platform.plans.index')"
                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20"
            >
                Edit plans in console
            </Link>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="plan in plans"
                :key="plan.id"
                class="flex flex-col rounded-2xl border bg-white p-5 shadow-sm"
                :class="subscription?.plan_id === plan.id ? 'border-brand-teal ring-1 ring-brand-teal/40' : 'border-gray-100'"
            >
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold text-brand-navy">{{ plan.name }}</h3>
                    <span v-if="!plan.is_active" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500">Hidden</span>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ price(plan) }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ plan.module_codes.length }} modules</p>
                <div class="mt-4 flex-1" />
                <span
                    v-if="subscription?.plan_id === plan.id"
                    class="rounded-lg bg-brand-teal/10 px-3 py-2 text-center text-xs font-semibold text-brand-teal-dark"
                >
                    Current plan
                </span>
                <button
                    v-else
                    type="button"
                    class="rounded-lg px-3 py-2 text-xs font-semibold text-brand-navy ring-1 ring-gray-200 transition hover:bg-gray-50 disabled:opacity-50"
                    :disabled="assignForm.processing"
                    @click="assign(plan.id)"
                >
                    Switch shop to {{ plan.name }}
                </button>
            </article>
        </div>

        <section class="admin-card">
            <h2 class="text-sm font-semibold text-brand-navy">Module status</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="mod in modules" :key="mod.code" class="rounded-lg border border-gray-100 px-3 py-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-brand-navy">{{ mod.name }}</span>
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                            :class="mod.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ mod.enabled ? 'On' : 'Locked' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">{{ mod.code }}{{ mod.is_core ? ' · core' : '' }}</p>
                </div>
            </div>
        </section>
    </div>
</template>
