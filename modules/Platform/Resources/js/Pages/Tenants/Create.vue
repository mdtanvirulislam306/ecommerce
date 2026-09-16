<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    slug: '',
    domain: '',
    owner_name: '',
    owner_email: '',
    owner_password: '',
    plan_id: props.plans[0]?.id ?? null,
    payment_note: '',
    ends_at: '',
    notes: '',
    status: 'active',
    module_overrides: {},
});

const nonCoreModules = computed(() => props.modules.filter((m) => !m.is_core));

const submit = () => form.post(route('platform.tenants.store'));
</script>

<template>
    <Head title="Create Tenant" />
    <AdminLayout title="Platform · Create Tenant">
        <div class="mb-5">
            <Link :href="route('platform.tenants.index')" class="text-sm font-medium text-brand-navy hover:text-brand-orange">← Tenants</Link>
        </div>

        <form class="mx-auto max-w-3xl space-y-5" @submit.prevent="submit">
            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Shop</h2>
                <div>
                    <InputLabel value="Shop name" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Slug (optional)" />
                        <TextInput v-model="form.slug" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Primary domain" />
                        <TextInput v-model="form.domain" class="mt-1 block w-full" placeholder="shop.example.com" required />
                        <InputError :message="form.errors.domain" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Notes" />
                    <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Owner account</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Owner name" />
                        <TextInput v-model="form.owner_name" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel value="Owner email" />
                        <TextInput v-model="form.owner_email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.owner_email" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Temporary password" />
                    <TextInput v-model="form.owner_password" type="password" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.owner_password" />
                </div>
            </section>

            <section class="admin-card space-y-4">
                <h2 class="text-sm font-semibold text-brand-navy">Subscription</h2>
                <div>
                    <InputLabel value="Plan" />
                    <select v-model="form.plan_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                            {{ plan.name }} ({{ plan.code }})
                        </option>
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Payment note" />
                        <TextInput v-model="form.payment_note" class="mt-1 block w-full" placeholder="Manual bkash / bank ref" />
                    </div>
                    <div>
                        <InputLabel value="Ends at (optional)" />
                        <TextInput v-model="form.ends_at" type="date" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Module overrides (optional)</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label v-for="mod in nonCoreModules" :key="mod.code" class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm">
                            <input v-model="form.module_overrides[mod.code]" type="checkbox" class="rounded border-gray-300 text-brand-orange" />
                            <span>{{ mod.name }}</span>
                        </label>
                    </div>
                    <p class="mt-2 text-[11px] text-gray-400">Checked = force enable on top of plan. Unchecked overrides are ignored on create.</p>
                </div>
            </section>

            <div class="flex gap-3">
                <PrimaryButton :disabled="form.processing">Provision shop</PrimaryButton>
                <Link :href="route('platform.tenants.index')"><SecondaryButton type="button">Cancel</SecondaryButton></Link>
            </div>
        </form>
    </AdminLayout>
</template>
