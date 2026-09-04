<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Store Dashboard" />

    <AdminLayout title="Store Dashboard">
        <p class="mb-5 text-sm text-gray-500">Overview of your storefront configuration and content.</p>

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Domains</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.domains }}</p>
                <p class="text-xs text-gray-400">{{ stats.active_domains }} active</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">CMS pages</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.pages }}</p>
                <p class="text-xs text-gray-400">{{ stats.published_pages }} published</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Published themes</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.published_themes }}</p>
                <p class="text-xs text-gray-400">{{ stats.installed_themes }} installed</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Online orders</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.orders }}</p>
                <p class="text-xs text-gray-400">{{ stats.pending_orders }} pending</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link :href="route('ecommerce.store.settings.index')" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy">
                Store settings
            </Link>
            <Link :href="route('ecommerce.pages.all.index')" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy">
                All pages
            </Link>
            <Link :href="route('ecommerce.online-orders.index')" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-brand-navy">
                Online orders
            </Link>
        </div>
    </AdminLayout>
</template>
