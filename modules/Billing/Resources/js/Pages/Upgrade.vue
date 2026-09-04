<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    module: { type: Object, required: true },
});
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
            <Link
                :href="route('billing.plans.index')"
                class="mt-6 inline-flex rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white"
            >
                View plans
            </Link>
        </div>
    </AdminLayout>
</template>
