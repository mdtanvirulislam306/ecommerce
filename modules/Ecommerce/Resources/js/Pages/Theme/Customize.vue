<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    theme: { type: Object, default: null },
    settings: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    primary_color: props.settings.primary_color ?? '#1e3a5f',
    logo_url: props.settings.logo_url ?? '',
    header_html: props.settings.header_html ?? '',
});

const save = () => {
    form.put(route('ecommerce.theme.customize.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Customize Theme" />

    <AdminLayout title="Customize Theme">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div v-if="!theme" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No active theme.
            <Link :href="route('ecommerce.theme.library')" class="font-medium underline">Install a theme</Link>
            first.
        </div>

        <form v-else class="max-w-2xl space-y-4" @submit.prevent="save">
            <p class="text-sm text-gray-500">Editing: <strong>{{ theme.name }}</strong></p>
            <div>
                <InputLabel value="Primary color" />
                <TextInput v-model="form.primary_color" type="color" class="mt-1 block h-10 w-24" />
                <InputError class="mt-1" :message="form.errors.primary_color" />
            </div>
            <div>
                <InputLabel value="Logo URL" />
                <TextInput v-model="form.logo_url" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.logo_url" />
            </div>
            <div>
                <InputLabel value="Header HTML" />
                <textarea v-model="form.header_html" rows="6" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm" />
                <InputError class="mt-1" :message="form.errors.header_html" />
            </div>
            <PrimaryButton :disabled="form.processing">Save customization</PrimaryButton>
        </form>
    </AdminLayout>
</template>
