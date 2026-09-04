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
    company_name: props.settings?.company_name ?? '',
    legal_name: props.settings?.legal_name ?? '',
    email: props.settings?.email ?? '',
    phone: props.settings?.phone ?? '',
    address: props.settings?.address ?? '',
    tax_id: props.settings?.tax_id ?? '',
});
const submit = () => form.put(route('settings.business.company.update'), { preserveScroll: true });
</script>
<template>
    <Head title="Company Profile" />
    <AdminLayout title="Company Profile">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            <div><InputLabel value="Company name" /><TextInput v-model="form.company_name" class="mt-1 block w-full" /><InputError :message="form.errors.company_name" /></div>
<div><InputLabel value="Legal name" /><TextInput v-model="form.legal_name" class="mt-1 block w-full" /><InputError :message="form.errors.legal_name" /></div>
<div><InputLabel value="Email" /><TextInput v-model="form.email" class="mt-1 block w-full" /><InputError :message="form.errors.email" /></div>
<div><InputLabel value="Phone" /><TextInput v-model="form.phone" class="mt-1 block w-full" /><InputError :message="form.errors.phone" /></div>
<div><InputLabel value="Address" /><TextInput v-model="form.address" class="mt-1 block w-full" /><InputError :message="form.errors.address" /></div>
<div><InputLabel value="Tax ID" /><TextInput v-model="form.tax_id" class="mt-1 block w-full" /><InputError :message="form.errors.tax_id" /></div>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>