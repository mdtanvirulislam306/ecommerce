<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Ecommerce Reports" />

    <AdminLayout title="Ecommerce Reports">
        <p class="mb-5 text-sm text-gray-500">Storefront performance and configuration summary.</p>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Domains</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.domains }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">CMS pages</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.pages }}</p>
                <p class="text-xs text-gray-400">{{ stats.published_pages }} published</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Coupons</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.coupons }}</p>
                <p class="text-xs text-gray-400">{{ stats.active_coupons }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Promotions</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.promotions }}</p>
                <p class="text-xs text-gray-400">{{ stats.active_promotions }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Published themes</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.published_themes }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Total orders</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.orders }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Pending orders</p>
                <p class="text-2xl font-semibold text-amber-700">{{ stats.pending_orders }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Confirmed revenue</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.revenue }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
