<script setup>
import PlanForm from '../../Components/PlanForm.vue';
import PlatformShell from '../../Components/PlatformShell.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps({
    modules: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    code: '',
    description: '',
    price: '',
    is_active: true,
    is_default: false,
    module_codes: [],
});

const submit = () =>
    form
        .transform(({ price, ...data }) => ({ ...data, price_monthly: Math.round(Number(price || 0) * 100) }))
        .post(route('platform.plans.store'), { preserveScroll: true });
</script>

<template>
    <PlatformShell title="New plan">
        <div>
            <Link :href="route('platform.plans.index')" class="inline-flex items-center gap-1 text-sm font-medium text-brand-navy hover:text-brand-orange">← All plans</Link>
            <h1 class="mt-2 text-xl font-semibold text-brand-navy">Create a plan</h1>
            <p class="text-sm text-gray-500">Choose a price and the modules shops unlock on it.</p>
        </div>

        <PlanForm :form="form" :modules="modules" is-new submit-label="Create plan" @submit="submit" />
    </PlatformShell>
</template>
