<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    presets: { type: Array, default: () => [] },
    installedCodes: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const form = useForm({ code: '' });

const install = (code) => {
    form.code = code;
    form.post(route('ecommerce.theme.library.install'), { preserveScroll: true });
};

const isInstalled = (code) => props.installedCodes.includes(code);
</script>

<template>
    <Head title="Theme Library" />

    <AdminLayout title="Theme Library">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-5 text-sm text-gray-500">Install a preset theme, then customize and publish it to the storefront.</p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="preset in presets" :key="preset.code" class="admin-card flex flex-col">
                <h3 class="font-semibold text-brand-navy">{{ preset.name }}</h3>
                <p class="mt-2 flex-1 text-sm text-gray-500">{{ preset.description }}</p>
                <div class="mt-4">
                    <PrimaryButton
                        v-if="!isInstalled(preset.code)"
                        :disabled="form.processing && form.code === preset.code"
                        @click="install(preset.code)"
                    >
                        Install
                    </PrimaryButton>
                    <span v-else class="text-sm font-medium text-emerald-700">Installed</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
