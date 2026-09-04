<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
    <Head title="Module Manager" />
    <AdminLayout title="Module Manager">
        <div class="admin-card space-y-3">
            <p class="text-sm text-gray-600">Module Manager is managed here. Core configuration lives under General and Business profile.</p>
            <Link href="/admin/billing/plans" class="text-sm text-brand-orange">Open plans & modules</Link>
        </div>
    </AdminLayout>
</template>