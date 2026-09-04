<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    program: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    name: props.program?.name ?? 'Default loyalty program',
    points_per_currency: props.program?.points_per_currency ?? 1,
    redemption_rate: props.program?.redemption_rate ?? 0.01,
    is_active: props.program?.is_active ?? true,
});

const submit = () => {
    if (props.program) {
        form.put(route('commerce.loyalty.program.update', props.program.id), { preserveScroll: true });
    } else {
        form.post(route('commerce.loyalty.program.store'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Loyalty Program" />

    <AdminLayout title="Loyalty Program">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-4 text-sm text-gray-500">Configure earn and redemption rates for customer loyalty points.</p>

        <form class="admin-card max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel value="Program name" />
                <TextInput v-model="form.name" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel value="Points per currency unit" />
                <TextInput v-model="form.points_per_currency" type="number" step="0.0001" min="0" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.points_per_currency" />
            </div>
            <div>
                <InputLabel value="Redemption rate (currency per point)" />
                <TextInput v-model="form.redemption_rate" type="number" step="0.0001" min="0" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.redemption_rate" />
            </div>
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.is_active" />
                <span class="text-sm">Active</span>
            </label>
            <PrimaryButton :disabled="form.processing">{{ program ? 'Save changes' : 'Create program' }}</PrimaryButton>
        </form>
    </AdminLayout>
</template>
