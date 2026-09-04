<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
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
    shipping_enabled: props.settings.shipping_enabled === '1' || props.settings.shipping_enabled === true,
    default_rate: props.settings.default_rate ?? '0',
    free_shipping_threshold: props.settings.free_shipping_threshold ?? '',
});

const save = () => {
    form.put(route('ecommerce.shipping.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Shipping Settings" />

    <AdminLayout title="Shipping Settings">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="max-w-2xl space-y-4" @submit.prevent="save">
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.shipping_enabled" />
                <span class="text-sm">Enable shipping</span>
            </label>
            <div>
                <InputLabel value="Default shipping rate" />
                <TextInput v-model="form.default_rate" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.default_rate" />
            </div>
            <div>
                <InputLabel value="Free shipping threshold (optional)" />
                <TextInput v-model="form.free_shipping_threshold" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.free_shipping_threshold" />
            </div>
            <PrimaryButton :disabled="form.processing">Save shipping</PrimaryButton>
        </form>
    </AdminLayout>
</template>
