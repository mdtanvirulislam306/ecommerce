<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    tenants: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Platform Tenants" />
    <AdminLayout title="Platform · Tenants">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Provision shops, domains, plans, and module access.</p>
            <Link :href="route('platform.tenants.create')">
                <PrimaryButton type="button">Create tenant</PrimaryButton>
            </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Shop</th>
                        <th class="px-4 py-3">Domain</th>
                        <th class="px-4 py-3">Plan</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-gray-50/80">
                        <td class="px-4 py-3">
                            <p class="font-medium text-brand-navy">{{ tenant.name }}</p>
                            <p class="text-xs text-gray-400">{{ tenant.slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ tenant.domain || '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ tenant.plan_name || '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-brand-navy">{{ tenant.owner_name || '—' }}</p>
                            <p class="text-xs text-gray-400">{{ tenant.owner_email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                :class="tenant.status === 'active' || tenant.status === 'trial' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                            >
                                {{ tenant.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('platform.tenants.show', tenant.id)" class="text-sm font-medium text-brand-orange hover:underline">
                                Manage
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!tenants.length">
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">No tenants yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
