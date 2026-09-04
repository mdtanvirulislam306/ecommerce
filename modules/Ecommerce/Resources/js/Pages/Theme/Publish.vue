<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    activeTheme: { type: Object, default: null },
    publishedTheme: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const publishForm = useForm({});

const publish = () => {
    publishForm.post(route('ecommerce.theme.publish'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Publish Theme" />

    <AdminLayout title="Publish Theme">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div v-if="!activeTheme" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No active theme to publish.
            <Link :href="route('ecommerce.theme.installed')" class="font-medium underline">Activate a theme</Link>
            first.
        </div>

        <div v-else class="max-w-xl space-y-4">
            <div class="admin-card">
                <p class="text-xs text-gray-500">Active theme</p>
                <p class="text-lg font-semibold text-brand-navy">{{ activeTheme.name }}</p>
            </div>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Currently published</p>
                <p class="text-lg font-semibold text-brand-navy">
                    {{ publishedTheme?.name ?? 'None' }}
                </p>
            </div>
            <PrimaryButton :disabled="publishForm.processing" @click="publish">
                Publish active theme
            </PrimaryButton>
        </div>
    </AdminLayout>
</template>
