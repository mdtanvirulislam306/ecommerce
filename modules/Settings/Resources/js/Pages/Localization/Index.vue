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
    language: props.settings?.language ?? '',
    date_format: props.settings?.date_format ?? '',
    time_format: props.settings?.time_format ?? '',
    first_day_of_week: props.settings?.first_day_of_week ?? '',
});
const submit = () => form.put(route('settings.localization.update'), { preserveScroll: true });
</script>
<template>
    <Head title="Localization" />
    <AdminLayout title="Localization">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            <div><InputLabel value="Language" /><TextInput v-model="form.language" class="mt-1 block w-full" /><InputError :message="form.errors.language" /></div>
<div><InputLabel value="Date format" /><TextInput v-model="form.date_format" class="mt-1 block w-full" /><InputError :message="form.errors.date_format" /></div>
<div><InputLabel value="Time format" /><TextInput v-model="form.time_format" class="mt-1 block w-full" /><InputError :message="form.errors.time_format" /></div>
<div><InputLabel value="First day of week (0=Sun)" /><TextInput v-model="form.first_day_of_week" class="mt-1 block w-full" /><InputError :message="form.errors.first_day_of_week" /></div>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>