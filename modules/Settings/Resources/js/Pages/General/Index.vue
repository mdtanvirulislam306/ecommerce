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
    shop_name: props.settings?.shop_name ?? '',
    timezone: props.settings?.timezone ?? '',
    locale: props.settings?.locale ?? '',
    default_currency: props.settings?.default_currency ?? '',
});
const submit = () => form.put(route('settings.general.update'), { preserveScroll: true });
</script>
<template>
    <Head title="General Settings" />
    <AdminLayout title="General Settings">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            <div><InputLabel value="Shop name" /><TextInput v-model="form.shop_name" class="mt-1 block w-full" /><InputError :message="form.errors.shop_name" /></div>
<div><InputLabel value="Timezone" /><TextInput v-model="form.timezone" class="mt-1 block w-full" /><InputError :message="form.errors.timezone" /></div>
<div><InputLabel value="Locale" /><TextInput v-model="form.locale" class="mt-1 block w-full" /><InputError :message="form.errors.locale" /></div>
<div><InputLabel value="Default currency" /><TextInput v-model="form.default_currency" class="mt-1 block w-full" /><InputError :message="form.errors.default_currency" /></div>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>