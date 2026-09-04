<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps({ settings: { type: Object, default: () => ({}) } });
const flash = computed(() => usePage().props.flash);
const form = useForm({
    stripe_key: props.settings?.stripe_key ?? '',
    sms_api_key: props.settings?.sms_api_key ?? '',
    google_maps_key: props.settings?.google_maps_key ?? '',
});
const submit = () => form.put(route('settings.integrations.update'), { preserveScroll: true });
</script>
<template>
    <Head title="Integrations" />
    <AdminLayout title="Integrations">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            <div><InputLabel value="Stripe key" /><TextInput v-model="form.stripe_key" class="mt-1 block w-full" /><InputError :message="form.errors.stripe_key" /></div>
<div><InputLabel value="SMS API key" /><TextInput v-model="form.sms_api_key" class="mt-1 block w-full" /><InputError :message="form.errors.sms_api_key" /></div>
<div><InputLabel value="Google Maps key" /><TextInput v-model="form.google_maps_key" class="mt-1 block w-full" /><InputError :message="form.errors.google_maps_key" /></div>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>