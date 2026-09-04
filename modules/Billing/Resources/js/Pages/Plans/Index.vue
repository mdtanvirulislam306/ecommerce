<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    subscription: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const forms = reactive(
    Object.fromEntries(
        props.plans.map((plan) => [
            plan.id,
            useForm({
                name: plan.name,
                description: plan.description || '',
                price_monthly: plan.price_monthly,
                is_active: plan.is_active,
                module_codes: [...plan.module_codes],
            }),
        ]),
    ),
);

const assignForm = useForm({});

const toggleModule = (planId, code, isCore) => {
    if (isCore) return;
    const form = forms[planId];
    const idx = form.module_codes.indexOf(code);
    if (idx >= 0) form.module_codes.splice(idx, 1);
    else form.module_codes.push(code);
};

const save = (planId) => {
    forms[planId].put(route('billing.plans.update', planId), { preserveScroll: true });
};

const assign = (planId) => {
    assignForm.post(route('billing.plans.assign', planId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Plans" />

    <AdminLayout title="Plans & modules">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <p class="mb-4 text-sm text-gray-500">
            Current plan:
            <span class="font-medium text-brand-navy">{{ subscription?.plan_name || 'None' }}</span>
        </p>

        <div class="mb-6 admin-card">
            <h2 class="text-sm font-semibold text-brand-navy">Module status</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="mod in modules"
                    :key="mod.code"
                    class="rounded border border-gray-100 px-3 py-2 text-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-brand-navy">{{ mod.name }}</span>
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                            :class="mod.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ mod.enabled ? 'On' : 'Locked' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">{{ mod.code }}{{ mod.is_core ? ' · core' : '' }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <section v-for="plan in plans" :key="plan.id" class="admin-card space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-brand-navy">{{ plan.name }}</h2>
                        <p class="text-xs text-gray-500">{{ plan.code }}</p>
                    </div>
                    <PrimaryButton
                        v-if="subscription?.plan_id !== plan.id"
                        type="button"
                        :disabled="assignForm.processing"
                        @click="assign(plan.id)"
                    >
                        Switch shop to this plan
                    </PrimaryButton>
                    <span v-else class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                        Active
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs text-gray-500">Name</label>
                        <TextInput v-model="forms[plan.id].name" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Monthly price (minor units)</label>
                        <TextInput v-model="forms[plan.id].price_monthly" type="number" min="0" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs text-gray-500">Description</label>
                        <TextInput v-model="forms[plan.id].description" class="mt-1 block w-full" />
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-xs font-medium text-gray-500">Unlocked modules</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="mod in modules"
                            :key="`${plan.id}-${mod.code}`"
                            class="flex items-center gap-2 text-sm"
                            :class="mod.is_core ? 'opacity-60' : ''"
                        >
                            <Checkbox
                                :checked="mod.is_core || forms[plan.id].module_codes.includes(mod.code)"
                                :disabled="mod.is_core"
                                @update:checked="toggleModule(plan.id, mod.code, mod.is_core)"
                            />
                            {{ mod.name }}
                        </label>
                    </div>
                </div>

                <PrimaryButton type="button" :disabled="forms[plan.id].processing" @click="save(plan.id)">
                    Save plan
                </PrimaryButton>
            </section>
        </div>
    </AdminLayout>
</template>
