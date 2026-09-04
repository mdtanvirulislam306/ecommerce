<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    channels: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Marketing Overview" />

    <AdminLayout title="Marketing Overview">
        <p class="mb-5 text-sm text-gray-500">
            Stories, campaigns, segments, and promotional tools in one place.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Stories</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.stories }}</p>
                <p class="text-xs text-gray-400">{{ stats.stories_active }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Campaigns</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.campaigns }}</p>
                <p class="text-xs text-gray-400">{{ stats.campaigns_sent }} sent</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Segments</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.segments }}</p>
                <p class="text-xs text-gray-400">{{ stats.segments_active }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Referrals</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.referrals }}</p>
                <p class="text-xs text-gray-400">{{ stats.referrals_pending }} pending</p>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Promotions</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.promotions }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Coupons</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.coupons }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Loyalty programs</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.loyalty_programs }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Draft campaigns</p>
                <p class="text-2xl font-semibold text-gray-700">{{ stats.campaigns_draft }}</p>
            </div>
        </div>

        <section class="admin-card mb-6">
            <h2 class="text-sm font-semibold text-brand-navy">Campaigns by channel</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="ch in channels" :key="ch.value" class="rounded-lg border border-gray-100 p-3">
                    <p class="text-xs text-gray-500">{{ ch.label }}</p>
                    <p class="text-xl font-semibold text-brand-navy">{{ ch.count }}</p>
                    <Link :href="route('marketing.' + ch.value + '.index')" class="text-xs text-brand-orange hover:underline">
                        Manage →
                    </Link>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-2">
            <Link :href="route('marketing.stories.index')" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">
                Stories
            </Link>
            <Link :href="route('marketing.campaigns.index')" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy">
                All campaigns
            </Link>
            <Link :href="route('marketing.segments.index')" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy">
                Segments
            </Link>
            <Link :href="route('marketing.reports')" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy">
                Reports
            </Link>
        </div>
    </AdminLayout>
</template>
