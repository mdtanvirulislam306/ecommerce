<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    module: { type: Object, required: true },
});

const page = usePage();
const isPlatformAdmin = computed(() => Boolean(page.props.auth?.user?.is_platform_admin));
</script>

<template>
    <Head title="Upgrade required" />

    <AdminLayout title="Upgrade required">
        <div class="mx-auto max-w-lg admin-card text-center">
            <p class="text-sm uppercase tracking-wide text-brand-orange">Plan locked</p>
            <h1 class="mt-2 text-2xl font-semibold text-brand-navy">{{ module.name }}</h1>
            <p class="mt-3 text-sm text-gray-600">
                {{ module.description || 'This module is not included in your current subscription.' }}
            </p>
            <p class="mt-2 text-xs text-gray-500">Module code: {{ module.code }}</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <Link
                    :href="route('settings.subscription')"
                    class="inline-flex rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white"
                >
                    View your plan
                </Link>
                <Link
                    :href="route('settings.modules')"
                    class="inline-flex rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-brand-navy"
                >
                    See modules
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
