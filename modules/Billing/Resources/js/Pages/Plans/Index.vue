<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PlatformEditor from './PlatformEditor.vue';
import { moduleGatePath } from '@/navigation/modules';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    subscription: { type: Object, default: null },
});

const page = usePage();
const isPlatformAdmin = computed(() => page.props.auth?.user?.is_platform_admin === true);

const endsOn = computed(() => formatPlanDate(props.subscription?.ends_at));

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

    <AdminLayout :title="isPlatformAdmin ? 'This shop\'s plan' : 'Your plan'">
        <PlatformEditor
            v-if="isPlatformAdmin"
            :plans="plans"
            :modules="modules"
            :subscription="subscription"
        />

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
