<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
    recent: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="HRM Overview" />

    <AdminLayout title="HRM Overview">
        <p class="mb-5 text-sm text-gray-500">
            HR directory, attendance, leave, payroll, and salary structures.
        </p>

        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Employees</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.employees }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Active</p>
                <p class="text-2xl font-semibold text-emerald-700">{{ stats.active }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Departments</p>
                <p class="text-2xl font-semibold text-brand-navy">{{ stats.departments }}</p>
            </div>
        </div>

        <div class="mb-4">
            <Link :href="route('hrm.employees.index')" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">
                Manage employees
            </Link>
        </div>

        <section class="admin-card">
            <h2 class="text-sm font-semibold text-brand-navy">Recent employees</h2>
            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                <li v-for="row in recent" :key="row.id" class="flex justify-between py-2">
                    <span class="font-medium text-brand-navy">{{ row.name }}</span>
                    <span class="text-gray-500">{{ row.department || '—' }}</span>
                </li>
                <li v-if="!recent.length" class="py-6 text-center text-gray-500">No employees yet.</li>
            </ul>
        </section>
    </AdminLayout>
</template>
