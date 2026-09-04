<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    guest_checkout: props.settings.guest_checkout === '1' || props.settings.guest_checkout === true,
    require_phone: props.settings.require_phone === '1' || props.settings.require_phone === true,
    payment_cod_enabled: props.settings.payment_cod_enabled === '1' || props.settings.payment_cod_enabled === true,
});

const save = () => {
    form.put(route('ecommerce.checkout.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Checkout Settings" />

    <AdminLayout title="Checkout Settings">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="max-w-2xl space-y-4" @submit.prevent="save">
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.guest_checkout" />
                <span class="text-sm">Allow guest checkout</span>
            </label>
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.require_phone" />
                <span class="text-sm">Require phone number</span>
            </label>
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.payment_cod_enabled" />
                <span class="text-sm">Enable cash on delivery</span>
            </label>
            <PrimaryButton :disabled="form.processing">Save checkout settings</PrimaryButton>
        </form>
    </AdminLayout>
</template>
