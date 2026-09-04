<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    store_name: props.settings.store_name ?? '',
    support_email: props.settings.support_email ?? '',
    currency: props.settings.currency ?? 'BDT',
    timezone: props.settings.timezone ?? 'Asia/Dhaka',
});

const save = () => {
    form.put(route('ecommerce.store.settings.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Store Settings" />

    <AdminLayout title="Store Settings">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="max-w-2xl space-y-4" @submit.prevent="save">
            <div>
                <InputLabel value="Store name" />
                <TextInput v-model="form.store_name" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.store_name" />
            </div>
            <div>
                <InputLabel value="Support email" />
                <TextInput v-model="form.support_email" type="email" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.support_email" />
            </div>
            <div>
                <InputLabel value="Currency (ISO 4217)" />
                <TextInput v-model="form.currency" class="mt-1 block w-full uppercase" maxlength="3" required />
                <InputError class="mt-1" :message="form.errors.currency" />
            </div>
            <div>
                <InputLabel value="Timezone" />
                <TextInput v-model="form.timezone" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.timezone" />
            </div>
            <PrimaryButton :disabled="form.processing">Save settings</PrimaryButton>
        </form>
    </AdminLayout>
</template>
