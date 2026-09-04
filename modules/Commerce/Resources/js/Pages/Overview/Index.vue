<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
});

const quickLinks = [
    { label: 'Promotions', route: 'commerce.promotions.index' },
    { label: 'Coupons', route: 'commerce.coupons.index' },
    { label: 'Referrals', route: 'commerce.referrals.index' },
    { label: 'Shipments', route: 'commerce.shipping.shipments.index' },
    { label: 'Wallets', route: 'commerce.wallet.wallets.index' },
    { label: 'Loyalty program', route: 'commerce.loyalty.program.index' },
];
</script>

<template>
    <Head title="Commerce Overview" />

    <AdminLayout title="Commerce Overview">
        <p class="mb-5 text-sm text-gray-500">
            Promotions, coupons, referrals, shipping, and wallet snapshot.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Promotions</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.promotions.promotions }}</p>
                <p class="text-xs text-gray-400">{{ stats.promotions.active_promotions }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Coupons</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.coupons.total }}</p>
                <p class="text-xs text-gray-400">{{ stats.coupons.active }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Referrals</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.referrals.total }}</p>
                <p class="text-xs text-gray-400">{{ stats.referrals.pending }} pending</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Shipments</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.shipments.total }}</p>
                <p class="text-xs text-gray-400">{{ stats.shipments.in_transit }} in transit</p>
            </div>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Customer wallets</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.wallets.total }}</p>
                <p class="text-xs text-gray-400">Balance: {{ stats.wallets.total_balance.toFixed(2) }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Commission rules</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.commissions.total }}</p>
                <p class="text-xs text-gray-400">{{ stats.commissions.active }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Promotion types</p>
                <p class="text-sm text-gray-600">
                    Rules {{ stats.promotions.discount_rules }} · BXGY {{ stats.promotions.buy_x_get_y }} · Free ship
                    {{ stats.promotions.free_shipping }}
                </p>
            </div>
        </div>

        <section class="admin-card">
            <h2 class="text-sm font-semibold text-brand-navy">Quick links</h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                <li v-for="link in quickLinks" :key="link.route">
                    <Link
                        :href="route(link.route)"
                        class="inline-flex rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-brand-navy hover:bg-gray-50"
                    >
                        {{ link.label }}
                    </Link>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
