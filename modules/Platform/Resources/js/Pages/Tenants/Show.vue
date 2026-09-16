<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tenant: { type: Object, required: true },
});

const suspend = () => router.post(route('platform.tenants.suspend', props.tenant.id));
const activate = () => router.post(route('platform.tenants.activate', props.tenant.id));
</script>

<template>
    <Head :title="`Tenant · ${tenant.name}`" />
    <AdminLayout :title="`Platform · ${tenant.name}`">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <Link :href="route('platform.tenants.index')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">← Tenants</Link>
            <div class="flex flex-wrap gap-2">
                <Link :href="route('platform.tenants.edit', tenant.id)"><SecondaryButton type="button">Edit</SecondaryButton></Link>
                <PrimaryButton v-if="tenant.status === 'suspended'" type="button" @click="activate">Activate</PrimaryButton>
                <button
                    v-else
                    type="button"
                    class="rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                    @click="suspend"
                >
                    Suspend
                </button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="admin-card space-y-2 text-sm">
                <h2 class="text-sm font-semibold text-brand-navy">Shop</h2>
                <p><span class="text-gray-500">Name:</span> {{ tenant.name }}</p>
                <p><span class="text-gray-500">Slug:</span> {{ tenant.slug }}</p>
                <p><span class="text-gray-500">Domain:</span> {{ tenant.domain || '—' }}</p>
                <p><span class="text-gray-500">Status:</span> <span class="capitalize">{{ tenant.status }}</span></p>
                <p v-if="tenant.notes"><span class="text-gray-500">Notes:</span> {{ tenant.notes }}</p>
            </section>
            <section class="admin-card space-y-2 text-sm">
                <h2 class="text-sm font-semibold text-brand-navy">Owner & plan</h2>
                <p><span class="text-gray-500">Owner:</span> {{ tenant.owner?.name }} ({{ tenant.owner?.email }})</p>
                <p><span class="text-gray-500">Plan ID:</span> {{ tenant.plan_id || '—' }}</p>
                <p><span class="text-gray-500">Payment note:</span> {{ tenant.payment_note || '—' }}</p>
                <p><span class="text-gray-500">Ends:</span> {{ tenant.ends_at || '—' }}</p>
            </section>
        </div>
    </AdminLayout>
</template>
