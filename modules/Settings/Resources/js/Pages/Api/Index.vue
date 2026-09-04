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
    api_token: props.settings?.api_token ?? '',
    webhook_url: props.settings?.webhook_url ?? '',
    webhook_secret: props.settings?.webhook_secret ?? '',
});
const submit = () => form.put(route('settings.api.update'), { preserveScroll: true });
</script>
<template>
    <Head title="API & Webhooks" />
    <AdminLayout title="API & Webhooks">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            <div><InputLabel value="API token" /><TextInput v-model="form.api_token" class="mt-1 block w-full" /><InputError :message="form.errors.api_token" /></div>
<div><InputLabel value="Webhook URL" /><TextInput v-model="form.webhook_url" class="mt-1 block w-full" /><InputError :message="form.errors.webhook_url" /></div>
<div><InputLabel value="Webhook secret" /><TextInput v-model="form.webhook_secret" class="mt-1 block w-full" /><InputError :message="form.errors.webhook_secret" /></div>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>