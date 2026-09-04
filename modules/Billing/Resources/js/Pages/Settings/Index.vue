<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    flags: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    multi_price: props.flags.multi_price,
    multi_warehouse: props.flags.multi_warehouse,
    multi_branch: props.flags.multi_branch,
});

const save = () => {
    form.put(route('billing.settings.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Shop complexity" />

    <AdminLayout title="Shop complexity">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-5 text-sm text-gray-500">
            Keep the UI simple by default. Turn flags on only when the merchant needs multi-price, multi-warehouse, or multi-branch.
        </p>

        <form class="admin-card max-w-lg space-y-4" @submit.prevent="save">
            <label class="flex items-start gap-3 text-sm">
                <Checkbox v-model:checked="form.multi_price" class="mt-0.5" />
                <span>
                    <span class="font-medium text-brand-navy">Multi-price</span>
                    <span class="block text-gray-500">Show price lists / customer group pricing screens</span>
                </span>
            </label>
            <label class="flex items-start gap-3 text-sm">
                <Checkbox v-model:checked="form.multi_warehouse" class="mt-0.5" />
                <span>
                    <span class="font-medium text-brand-navy">Multi-warehouse</span>
                    <span class="block text-gray-500">Show warehouse picker and multi-location stock UI</span>
                </span>
            </label>
            <label class="flex items-start gap-3 text-sm">
                <Checkbox v-model:checked="form.multi_branch" class="mt-0.5" />
                <span>
                    <span class="font-medium text-brand-navy">Multi-branch</span>
                    <span class="block text-gray-500">Enable branch-oriented POS / ops features</span>
                </span>
            </label>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>
