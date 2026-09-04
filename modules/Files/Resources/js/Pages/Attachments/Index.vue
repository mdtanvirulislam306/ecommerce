<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
    <Head title="Attachments" />
    <AdminLayout title="Attachments">
        <div class="admin-card text-sm text-gray-600">
            Attachments are stored with Media Library and Documents.
            <div class="mt-3 space-x-3">
                <Link :href="route('files.media-library.index')" class="text-brand-orange">Media library</Link>
                <Link :href="route('files.documents.index')" class="text-brand-orange">Documents</Link>
            </div>
        </div>
    </AdminLayout>
</template>