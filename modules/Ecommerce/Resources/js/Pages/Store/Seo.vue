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
    meta_title: props.settings.meta_title ?? '',
    meta_description: props.settings.meta_description ?? '',
    meta_keywords: props.settings.meta_keywords ?? '',
});

const save = () => {
    form.put(route('ecommerce.store.seo.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Store SEO" />

    <AdminLayout title="Store SEO">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-5 text-sm text-gray-500">Default meta tags applied across the storefront.</p>

        <form class="max-w-2xl space-y-4" @submit.prevent="save">
            <div>
                <InputLabel value="Meta title" />
                <TextInput v-model="form.meta_title" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.meta_title" />
            </div>
            <div>
                <InputLabel value="Meta description" />
                <textarea v-model="form.meta_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                <InputError class="mt-1" :message="form.errors.meta_description" />
            </div>
            <div>
                <InputLabel value="Meta keywords" />
                <TextInput v-model="form.meta_keywords" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.meta_keywords" />
            </div>
            <PrimaryButton :disabled="form.processing">Save SEO</PrimaryButton>
        </form>
    </AdminLayout>
</template>
