<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenant: { type: Object, required: true },
});

const form = useForm({
    name: props.tenant.name,
    status: props.tenant.status,
    notes: props.tenant.notes || '',
    domain: props.tenant.domain || '',
    plan_id: props.tenant.plan_id,
    payment_note: props.tenant.payment_note || '',
    ends_at: props.tenant.ends_at || '',
    module_overrides: { ...(props.tenant.module_overrides || {}) },
    owner_password: '',
});

const nonCoreModules = computed(() => (props.tenant.modules || []).filter((m) => !m.is_core));

const submit = () => form.put(route('platform.tenants.update', props.tenant.id));
</script>

<template>
    <PlatformShell :title="`Edit ${tenant.name}`">
        <div>
            <Link :href="route('platform.tenants.show', tenant.id)" class="text-sm font-medium text-brand-navy hover:text-brand-orange">← Back</Link>
        </div>

        <form class="mx-auto max-w-3xl space-y-5" @submit.prevent="submit">
            <section class="admin-card space-y-4">
                <div>
                    <InputLabel value="Shop name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Domain" />
                        <TextInput v-model="form.domain" class="mt-1 block w-full" />
                        <InputError :message="form.errors.domain" />
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="form.status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <option value="active">Active</option>
                            <option value="trial">Trial</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div>
                    <InputLabel value="Notes" />
                    <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <div>
                    <InputLabel value="Plan" />
                    <select v-model="form.plan_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option v-for="plan in tenant.plans || []" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Payment note" />
                        <TextInput v-model="form.payment_note" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Ends at" />
                        <TextInput v-model="form.ends_at" type="date" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Reset owner password (optional)" />
                    <TextInput v-model="form.owner_password" type="password" class="mt-1 block w-full" />
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label v-for="mod in nonCoreModules" :key="mod.code" class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm">
                        <input v-model="form.module_overrides[mod.code]" type="checkbox" class="rounded border-gray-300 text-brand-orange" />
                        <span>{{ mod.name }}</span>
                    </label>
                </div>
            </section>

            <div class="flex gap-3">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                <Link :href="route('platform.tenants.show', tenant.id)"><SecondaryButton type="button">Cancel</SecondaryButton></Link>
            </div>
        </form>
    </PlatformShell>
</template>
