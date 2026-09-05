<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const enabledModules = computed(() => page.props.enabledModules ?? []);
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout title="Dashboard">
        <div class="admin-card">
            <p class="text-sm text-gray-500">Welcome back, {{ user?.name }}</p>
            <h2 class="mt-1 text-xl font-semibold text-brand-navy">Owner overview</h2>
            <p class="mt-2 text-sm text-gray-600">
                Role-based dashboards (Owner, Sales Manager, Inventory, Accountant, Ecommerce) will replace this view.
            </p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs text-gray-500">Revenue</p>
                    <p class="mt-1 text-lg font-semibold text-brand-navy">—</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs text-gray-500">Orders</p>
                    <p class="mt-1 text-lg font-semibold text-brand-navy">—</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs text-gray-500">Low stock</p>
                    <p class="mt-1 text-lg font-semibold text-brand-orange">—</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <p class="text-xs text-gray-500">Modules</p>
                    <p class="mt-1 text-lg font-semibold text-brand-teal-dark">{{ enabledModules.length }}</p>
                </div>
            </div>
            <div class="mt-6">
                <Link
                    :href="route('sales.overview')"
                    class="text-sm font-medium text-brand-orange hover:text-brand-orange-dark"
                >
                    Go to Sales →
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
