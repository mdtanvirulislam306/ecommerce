<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    channels: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Marketing Reports" />

    <AdminLayout title="Marketing Reports">
        <p class="mb-5 text-sm text-gray-500">
            Aggregate marketing metrics across stories, campaigns, promotions, and referrals.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Total audience reach</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.total_audience }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Coupons redeemed</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.coupons_used }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Referral rewards paid</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.referral_rewards }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Scheduled campaigns</p>
                <p class="text-2xl font-semibold text-sky-700">{{ stats.campaigns_scheduled }}</p>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Stories</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.stories }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Campaigns sent</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.campaigns_sent }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Active promotions</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.promotions_active }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Completed referrals</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.referrals_completed }}</p>
            </div>
        </div>

        <section class="admin-card mb-6">
            <h2 class="text-sm font-semibold text-brand-navy">Campaigns by channel</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs text-gray-500">
                        <tr>
                            <th class="pb-2">Channel</th>
                            <th class="pb-2">Campaigns</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ch in channels" :key="ch.value" class="border-t border-gray-50">
                            <td class="py-2 font-medium text-brand-navy">{{ ch.label }}</td>
                            <td class="py-2">{{ ch.count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Link :href="route('marketing.overview')" class="text-sm text-brand-orange hover:underline">
            ← Back to overview
        </Link>
    </AdminLayout>
</template>
