<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { moduleGatePath } from '@/navigation/modules';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    subscription: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const isPlatformAdmin = computed(() => Boolean(page.props.auth?.user?.is_platform_admin));

const forms = reactive({});
const assignForm = useForm({});

if (isPlatformAdmin.value) {
    for (const plan of props.plans) {
        forms[plan.id] = useForm({
            name: plan.name,
            description: plan.description || '',
            price_monthly: plan.price_monthly,
            is_active: plan.is_active,
            module_codes: [...plan.module_codes],
        });
    }
}

const endsOn = computed(() => formatPlanDate(props.subscription?.ends_at));

const toggleModule = (planId, code, isCore) => {
    if (isCore) {
        return;
    }

    const form = forms[planId];
    const idx = form.module_codes.indexOf(code);

    if (idx >= 0) {
        form.module_codes.splice(idx, 1);
    } else {
        form.module_codes.push(code);
    }
};

const save = (planId) => {
    forms[planId].put(route('billing.plans.update', planId), { preserveScroll: true });
};

const assign = (planId) => {
    assignForm.post(route('billing.plans.assign', planId), { preserveScroll: true });
};

function formatPlanDate(value) {
    if (!value) {
        return null;
    }

    const [year, month, day] = String(value).split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return new Date(year, month - 1, day).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function upgradeHref(mod) {
    if (mod.enabled) {
        return null;
    }

    return moduleGatePath(mod.code);
}
</script>

<template>
    <Head title="Plans" />

    <AdminLayout :title="isPlatformAdmin ? 'Plans & modules' : 'Your plan'">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <template v-if="isPlatformAdmin">
            <p class="mb-4 text-sm text-gray-500">
                Platform admin. Current plan:
                <span class="font-medium text-brand-navy">{{ subscription?.plan_name || 'None' }}</span>
            </p>

            <div class="mb-6 admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Module status</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="mod in modules"
                        :key="mod.code"
                        class="rounded border border-gray-100 px-3 py-2 text-sm"
                    >
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
            </div>

            <div class="space-y-6">
                <section v-for="plan in plans" :key="plan.id" class="admin-card space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-brand-navy">{{ plan.name }}</h2>
                            <p class="text-xs text-gray-500">{{ plan.code }}</p>
                        </div>
                        <PrimaryButton
                            v-if="subscription?.plan_id !== plan.id"
                            type="button"
                            :disabled="assignForm.processing"
                            @click="assign(plan.id)"
                        >
                            Switch shop to this plan
                        </PrimaryButton>
                        <span v-else class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                            Active
                        </span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs text-gray-500">Name</label>
                            <TextInput v-model="forms[plan.id].name" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Monthly price (minor units)</label>
                            <TextInput v-model="forms[plan.id].price_monthly" type="number" min="0" class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs text-gray-500">Description</label>
                            <TextInput v-model="forms[plan.id].description" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-gray-500">Unlocked modules</p>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="mod in modules"
                                :key="`${plan.id}-${mod.code}`"
                                class="flex items-center gap-2 text-sm"
                                :class="mod.is_core ? 'opacity-60' : ''"
                            >
                                <Checkbox
                                    :checked="mod.is_core || forms[plan.id].module_codes.includes(mod.code)"
                                    :disabled="mod.is_core"
                                    @update:checked="toggleModule(plan.id, mod.code, mod.is_core)"
                                />
                                {{ mod.name }}
                            </label>
                        </div>
                    </div>

                    <PrimaryButton type="button" :disabled="forms[plan.id].processing" @click="save(plan.id)">
                        Save plan
                    </PrimaryButton>
                </section>
            </div>
        </template>

        <div v-else class="max-w-4xl space-y-4">
            <section class="admin-card">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-orange">Current plan</p>
                <template v-if="subscription">
                    <h2 class="mt-2 text-2xl font-semibold text-brand-navy">{{ subscription.plan_name || 'Plan' }}</h2>
                    <p v-if="subscription.plan_code" class="mt-1 text-sm text-gray-500">{{ subscription.plan_code }}</p>
                    <dl v-if="endsOn || subscription.payment_note" class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div v-if="endsOn">
                            <dt class="text-xs text-gray-500">Ends</dt>
                            <dd class="mt-1 text-sm font-medium text-brand-navy">{{ endsOn }}</dd>
                        </div>
                        <div v-if="subscription.payment_note" class="sm:col-span-2">
                            <dt class="text-xs text-gray-500">Payment note</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm text-brand-navy">{{ subscription.payment_note }}</dd>
                        </div>
                    </dl>
                </template>
                <h2 v-else class="mt-2 text-xl font-semibold text-brand-navy">No active subscription</h2>
                <p class="mt-4 text-sm text-gray-500">
                    Plan assignment is managed by the platform. Open your plan and modules for the read-only view.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <Link
                        :href="route('settings.subscription')"
                        class="inline-flex rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white"
                    >
                        Your plan
                    </Link>
                    <Link
                        :href="route('settings.modules')"
                        class="inline-flex rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-brand-navy"
                    >
                        Modules
                    </Link>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="text-sm font-semibold text-brand-navy">Module status</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="mod in modules"
                        :key="mod.code"
                        class="rounded border border-gray-100 px-3 py-2 text-sm"
                    >
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
                        <Link
                            v-if="upgradeHref(mod)"
                            :href="upgradeHref(mod)"
                            class="mt-2 inline-flex text-xs font-medium text-brand-orange"
                        >
                            View upgrade
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
