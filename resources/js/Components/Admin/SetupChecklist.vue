<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * Owner first-run checklist for the admin dashboard.
 * Renders nothing when the prop is missing, every step is done, or the shop dismissed it.
 */
const props = defineProps({
    checklist: {
        type: Object,
        default: null,
    },
});

const steps = [
    {
        key: 'shop_name',
        label: 'Shop name',
        hint: 'Settings · General',
        route: 'settings.general.edit',
    },
    {
        key: 'business_profile',
        label: 'Business profile',
        hint: 'Settings · Company',
        route: 'settings.business.company.edit',
    },
    {
        key: 'store_settings',
        label: 'Store settings',
        hint: 'Ecommerce · Store settings',
        route: 'ecommerce.store.settings.index',
    },
    {
        key: 'payment_method',
        label: 'Payment method',
        hint: 'Settings · Payment methods',
        route: 'settings.payment-methods.index',
    },
];

const dismissing = ref(false);

const visible = computed(() => {
    const checklist = props.checklist;

    if (checklist == null || typeof checklist !== 'object' || Array.isArray(checklist)) {
        return false;
    }

    return checklist.completed !== true && ! checklist.setup_dismissed_at;
});

const progress = computed(() => {
    const reported = Number(props.checklist?.progress);

    if (Number.isFinite(reported)) {
        return Math.min(steps.length, Math.max(0, reported));
    }

    return steps.filter((step) => props.checklist?.[step.key] === true).length;
});

const progressPercent = computed(() => (progress.value / steps.length) * 100);

function isDone(key) {
    return props.checklist?.[key] === true;
}

function dismiss() {
    if (dismissing.value) {
        return;
    }

    dismissing.value = true;

    router.post(route('setup-checklist.dismiss'), {}, {
        preserveScroll: true,
        onFinish: () => {
            dismissing.value = false;
        },
    });
}
</script>

<template>
    <section v-if="visible" class="admin-card" data-setup-checklist>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-brand-navy">Set up your shop</h2>
                <p class="mt-0.5 text-xs text-gray-400">Four steps before the shop is ready.</p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-sm font-semibold tabular-nums text-brand-navy" data-setup-progress>
                    {{ progress }} of 4
                </p>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:text-brand-navy disabled:opacity-50"
                    data-setup-dismiss
                    :disabled="dismissing"
                    @click="dismiss"
                >
                    {{ dismissing ? 'Dismissing…' : 'Dismiss' }}
                </button>
            </div>
        </div>

        <div
            class="mt-4 h-1.5 overflow-hidden rounded-full bg-gray-100"
            role="progressbar"
            :aria-valuenow="progress"
            aria-valuemin="0"
            aria-valuemax="4"
            :aria-label="`${progress} of 4 setup steps complete`"
        >
            <div class="h-full rounded-full bg-brand-teal" :style="{ width: `${progressPercent}%` }" />
        </div>

        <ul class="mt-4 grid gap-1 sm:grid-cols-2">
            <li v-for="step in steps" :key="step.key">
                <Link
                    :href="route(step.route)"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-gray-50"
                    :data-setup-item="step.key"
                    :data-setup-done="isDone(step.key) ? 'true' : 'false'"
                >
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                        :class="isDone(step.key) ? 'bg-brand-teal/15 text-brand-teal-dark' : 'border border-gray-200 bg-white'"
                    >
                        <svg
                            v-if="isDone(step.key)"
                            class="h-3.5 w-3.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.2 7.2a1 1 0 0 1-1.4 0L3.3 9.1a1 1 0 1 1 1.4-1.4l4.1 4.1 6.5-6.5a1 1 0 0 1 1.4 0Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span v-else class="h-1.5 w-1.5 rounded-full bg-brand-orange" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-brand-navy">{{ step.label }}</span>
                        <span class="block text-xs text-gray-400">{{ step.hint }}</span>
                    </span>
                    <span
                        class="shrink-0 text-xs font-medium"
                        :class="isDone(step.key) ? 'text-brand-teal-dark' : 'text-gray-300'"
                    >
                        {{ isDone(step.key) ? 'Done' : '→' }}
                    </span>
                </Link>
            </li>
        </ul>
    </section>
</template>
