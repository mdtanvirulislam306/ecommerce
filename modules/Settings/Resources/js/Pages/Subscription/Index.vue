<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const subscription = computed(() => page.props.subscription ?? null);
const isPlatformAdmin = computed(() => Boolean(page.props.auth?.user?.is_platform_admin));

const endsOn = computed(() => formatPlanDate(subscription.value?.ends_at));

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
</script>

<template>
    <Head title="Your plan" />

    <AdminLayout title="Your plan">
        <div v-if="!subscription" class="admin-card max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-orange">Subscription</p>
            <h2 class="mt-2 text-xl font-semibold text-brand-navy">No active subscription</h2>
            <p class="mt-2 text-sm text-gray-600">
                This shop has no plan on file. A platform administrator assigns the plan for this shop.
            </p>
            <Link :href="route('settings.modules')" class="mt-5 inline-flex text-sm font-medium text-brand-orange">
                View modules
            </Link>
        </div>

        <div v-else class="max-w-2xl space-y-4">
            <section class="admin-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-orange">Current plan</p>
                        <h2 class="mt-2 text-2xl font-semibold text-brand-navy">
                            {{ subscription.plan_name || 'Plan' }}
                        </h2>
                        <p v-if="subscription.plan_code" class="mt-1 text-sm text-gray-500">
                            {{ subscription.plan_code }}
                        </p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                        Active
                    </span>
                </div>

                <dl v-if="endsOn || subscription.payment_note" class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div v-if="endsOn">
                        <dt class="text-xs text-gray-500">Ends</dt>
                        <dd class="mt-1 text-sm font-medium text-brand-navy">{{ endsOn }}</dd>
                    </div>
                    <div v-if="subscription.payment_note" class="sm:col-span-2">
                        <dt class="text-xs text-gray-500">Payment note</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-brand-navy">{{ subscription.payment_note }}</dd>
                    </div>
                </dl>

                <p class="mt-6 text-sm text-gray-500">
                    Plan assignment is managed by the platform. This page shows the plan on this shop.
                </p>
            </section>

            <div class="flex flex-wrap gap-3">
                <Link
                    :href="route('settings.modules')"
                    class="inline-flex rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white"
                >
                    View modules
                </Link>
                <Link
                    v-if="isPlatformAdmin"
                    :href="route('billing.plans.index')"
                    class="inline-flex rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-brand-navy"
                >
                    Manage plans
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
