<script setup>
import PlanForm from '../../Components/PlanForm.vue';
import PlatformShell from '../../Components/PlatformShell.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    plan: { type: Object, required: true },
    modules: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.plan.name,
    code: props.plan.code,
    description: props.plan.description ?? '',
    price: props.plan.price_monthly / 100,
    is_active: props.plan.is_active,
    is_default: props.plan.is_default,
    module_codes: [...props.plan.module_codes],
});

const submit = () =>
    form
        .transform(({ price, code, ...data }) => ({ ...data, price_monthly: Math.round(Number(price || 0) * 100) }))
        .put(route('platform.plans.update', props.plan.id), { preserveScroll: true });
</script>

<template>
    <PlatformShell :title="`Edit ${plan.name}`">
        <div>
            <Link :href="route('platform.plans.index')" class="inline-flex items-center gap-1 text-sm font-medium text-brand-navy hover:text-brand-orange">← All plans</Link>
            <h1 class="mt-2 text-xl font-semibold text-brand-navy">Edit {{ plan.name }}</h1>
            <p class="text-sm text-gray-500">
                {{ plan.shops }} {{ plan.shops === 1 ? 'shop is' : 'shops are' }} on this plan. Module changes apply to them straight away.
            </p>
        </div>

        <PlanForm :form="form" :modules="modules" :locked-default="plan.is_default" submit-label="Save changes" @submit="submit" />
    </PlatformShell>
</template>
