<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    shipping_enabled: Boolean(props.settings.shipping_enabled),
    zones: (props.settings.zones?.length ? props.settings.zones : [{ name: 'Inside Dhaka', rate: 60 }, { name: 'Outside Dhaka', rate: 120 }])
        .map((zone) => ({ name: zone.name, rate: zone.rate })),
    free_shipping_threshold: props.settings.free_shipping_threshold ?? '',
});

const addZone = () => form.zones.push({ name: '', rate: '' });
const removeZone = (index) => form.zones.splice(index, 1);

const cheapestRate = computed(() => {
    const rates = form.zones.map((zone) => Number(zone.rate)).filter((rate) => !Number.isNaN(rate));
    return rates.length ? Math.min(...rates) : 0;
});

const save = () => {
    form.put(route('ecommerce.shipping.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Delivery Settings" />

    <AdminLayout title="Delivery Settings">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_18rem]" @submit.prevent="save">
            <div class="space-y-6">
                <section class="admin-card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-brand-navy">Charge for delivery</h2>
                            <p class="mt-1 text-sm text-gray-500">Customers choose their area at checkout and the charge is added to the order total.</p>
                        </div>
                        <ToggleSwitch v-model="form.shipping_enabled" label="Charge for delivery" />
                    </div>
                    <p v-if="!form.shipping_enabled" class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">
                        Delivery is free for every order. Customers won't be asked for a delivery area.
                    </p>
                </section>

                <section v-if="form.shipping_enabled" class="admin-card">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-brand-navy">Delivery areas</h2>
                            <p class="mt-1 text-sm text-gray-500">For example Inside Dhaka and Outside Dhaka, each with its own charge.</p>
                        </div>
                        <button type="button" class="shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-brand-navy hover:bg-gray-50" @click="addZone">
                            + Add area
                        </button>
                    </div>

                    <InputError class="mt-3" :message="form.errors.zones" />

                    <div class="mt-4 space-y-3">
                        <div v-for="(zone, index) in form.zones" :key="index" class="rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                            <div class="grid gap-3 sm:grid-cols-[1fr_10rem_auto] sm:items-end">
                                <div>
                                    <InputLabel :value="`Area ${index + 1}`" />
                                    <TextInput v-model="zone.name" type="text" class="mt-1 block w-full" placeholder="e.g. Inside Dhaka" />
                                </div>
                                <div>
                                    <InputLabel value="Charge (৳)" />
                                    <TextInput v-model="zone.rate" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                                </div>
                                <button
                                    type="button"
                                    class="h-10 rounded-lg px-3 text-sm text-red-600 hover:bg-red-50 disabled:opacity-40"
                                    :disabled="form.zones.length === 1"
                                    @click="removeZone(index)"
                                >
                                    Remove
                                </button>
                            </div>
                            <InputError class="mt-1" :message="form.errors[`zones.${index}.name`] || form.errors[`zones.${index}.rate`]" />
                        </div>
                    </div>
                </section>

                <section v-if="form.shipping_enabled" class="admin-card">
                    <h2 class="text-base font-semibold text-brand-navy">Free delivery</h2>
                    <p class="mt-1 text-sm text-gray-500">Waive the charge when the order (after coupon discount) reaches this amount. Leave empty to always charge.</p>
                    <div class="mt-4 max-w-xs">
                        <InputLabel value="Free delivery from (৳)" />
                        <TextInput v-model="form.free_shipping_threshold" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="e.g. 2000" />
                        <InputError class="mt-1" :message="form.errors.free_shipping_threshold" />
                    </div>
                </section>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Save delivery settings</PrimaryButton>
                </div>
            </div>

            <aside class="h-fit rounded-2xl bg-brand-navy p-5 text-white lg:sticky lg:top-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Customer sees at checkout</p>
                <div v-if="form.shipping_enabled" class="mt-4 space-y-2">
                    <div v-for="(zone, index) in form.zones" :key="index" class="flex items-center justify-between rounded-xl bg-white/10 px-3 py-2.5 text-sm ring-1 ring-white/10">
                        <span class="truncate">{{ zone.name || 'Unnamed area' }}</span>
                        <span class="font-semibold">৳{{ Number(zone.rate || 0).toFixed(0) }}</span>
                    </div>
                    <p v-if="form.free_shipping_threshold" class="pt-2 text-xs text-white/70">
                        Free delivery on orders from ৳{{ Number(form.free_shipping_threshold).toFixed(0) }}.
                    </p>
                    <p v-else class="pt-2 text-xs text-white/70">Delivery from ৳{{ cheapestRate.toFixed(0) }}.</p>
                </div>
                <p v-else class="mt-4 rounded-xl bg-white/10 px-3 py-2.5 text-sm">Free delivery</p>
            </aside>
        </form>
    </AdminLayout>
</template>
