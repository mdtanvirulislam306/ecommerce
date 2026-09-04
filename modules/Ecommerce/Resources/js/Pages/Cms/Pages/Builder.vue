<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
});

const pageProps = usePage();
const flash = computed(() => pageProps.props.flash);
const isEditing = computed(() => Boolean(props.page?.id));

const form = useForm({
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    body: props.page?.body ?? '',
    is_published: props.page?.is_published ?? false,
    seo_title: props.page?.seo_title ?? '',
    seo_description: props.page?.seo_description ?? '',
});

const save = () => {
    if (isEditing.value) {
        form.put(route('ecommerce.pages.builder.update', props.page.id), { preserveScroll: true });
    } else {
        form.post(route('ecommerce.pages.builder.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? `Edit: ${page.title}` : 'Page Builder'" />

    <AdminLayout :title="isEditing ? 'Edit Page' : 'New Page'">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4">
            <Link :href="route('ecommerce.pages.all.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← All pages
            </Link>
        </div>

        <form class="max-w-3xl space-y-4" @submit.prevent="save">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Title" />
                    <TextInput v-model="form.title" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.title" />
                </div>
                <div>
                    <InputLabel value="Slug (optional)" />
                    <TextInput v-model="form.slug" class="mt-1 block w-full" />
                    <InputError class="mt-1" :message="form.errors.slug" />
                </div>
            </div>
            <div>
                <InputLabel value="Body" />
                <textarea v-model="form.body" rows="14" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm" />
                <InputError class="mt-1" :message="form.errors.body" />
            </div>
            <div>
                <InputLabel value="SEO title" />
                <TextInput v-model="form.seo_title" class="mt-1 block w-full" />
            </div>
            <div>
                <InputLabel value="SEO description" />
                <textarea v-model="form.seo_description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
            </div>
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.is_published" />
                <span class="text-sm">Published</span>
            </label>
            <div class="flex gap-3">
                <PrimaryButton :disabled="form.processing">Save page</PrimaryButton>
                <Link :href="route('ecommerce.pages.all.index')" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm">
                    Cancel
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
