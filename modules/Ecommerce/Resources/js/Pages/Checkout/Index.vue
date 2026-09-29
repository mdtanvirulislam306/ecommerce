<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
    callbackUrls: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    payment_cod_enabled: props.settings.payment_cod_enabled,
    payment_online_enabled: props.settings.payment_online_enabled,
    sslcommerz_store_id: props.settings.sslcommerz_store_id ?? '',
    sslcommerz_store_password: '',
    sslcommerz_sandbox: props.settings.sslcommerz_sandbox,
    guest_checkout: props.settings.guest_checkout,
    require_phone: props.settings.require_phone,
});

const walletBrands = ['bKash', 'Nagad', 'Rocket', 'Visa', 'Mastercard'];

const isConnected = computed(() => props.settings.payment_online_enabled && props.settings.has_store_password && props.settings.sslcommerz_store_id);

const copiedField = ref(null);

const copy = async (field, value) => {
    try {
        await navigator.clipboard.writeText(value);
        copiedField.value = field;
        setTimeout(() => (copiedField.value = null), 2000);
    } catch {
        copiedField.value = null;
    }
};

const save = () => {
    form.put(route('ecommerce.checkout.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('sslcommerz_store_password'),
    });
};
</script>

<template>
    <Head title="Payments & Checkout" />

    <AdminLayout title="Payments & Checkout">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_18rem]" @submit.prevent="save">
            <div class="space-y-6">
                <section class="admin-card">
                    <h2 class="text-base font-semibold text-brand-navy">How customers pay</h2>
                    <p class="mt-1 text-sm text-gray-500">Turn on the payment methods you accept. Customers pick one when they place an order.</p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div
                            class="rounded-2xl border p-4 transition"
                            :class="form.payment_cod_enabled ? 'border-brand-teal/40 bg-brand-teal/5' : 'border-gray-200 bg-white'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                    </svg>
                                </span>
                                <ToggleSwitch v-model="form.payment_cod_enabled" label="Cash on delivery" />
                            </div>
                            <h3 class="mt-3 text-sm font-semibold text-brand-navy">Cash on delivery</h3>
                            <p class="mt-1 text-xs text-gray-500">Customer pays the rider in cash. You confirm the order by phone first.</p>
                        </div>

                        <div
                            class="rounded-2xl border p-4 transition"
                            :class="form.payment_online_enabled ? 'border-brand-teal/40 bg-brand-teal/5' : 'border-gray-200 bg-white'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-orange/10 text-brand-orange">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                </span>
                                <ToggleSwitch v-model="form.payment_online_enabled" label="Online payment" />
                            </div>
                            <h3 class="mt-3 text-sm font-semibold text-brand-navy">Online payment</h3>
                            <p class="mt-1 text-xs text-gray-500">Mobile wallets and cards through SSLCommerz. Money goes straight to your merchant account.</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span v-for="brand in walletBrands" :key="brand" class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-gray-600 ring-1 ring-gray-200">
                                    {{ brand }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <InputError class="mt-3" :message="form.errors.payment_cod_enabled" />
                </section>

                <section v-if="form.payment_online_enabled" class="admin-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-brand-navy">Connect SSLCommerz</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Copy these from your
                                <a href="https://merchant.sslcommerz.com" target="_blank" rel="noopener" class="font-medium text-brand-teal-dark hover:underline">SSLCommerz merchant panel</a>.
                                No account yet? Get free test credentials at
                                <a href="https://developer.sslcommerz.com/registration/" target="_blank" rel="noopener" class="font-medium text-brand-teal-dark hover:underline">developer.sslcommerz.com</a>.
                            </p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                            :class="isConnected ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-gray-50 text-gray-600 ring-gray-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-current" />
                            {{ isConnected ? 'Connected' : 'Not connected' }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="sslcommerz_store_id" value="Store ID" />
                            <TextInput id="sslcommerz_store_id" v-model="form.sslcommerz_store_id" type="text" class="mt-1 block w-full" autocomplete="off" placeholder="e.g. myshop66f1a2b3c4d5" />
                            <InputError class="mt-1" :message="form.errors.sslcommerz_store_id" />
                        </div>
                        <div>
                            <InputLabel for="sslcommerz_store_password" value="Store password" />
                            <TextInput
                                id="sslcommerz_store_password"
                                v-model="form.sslcommerz_store_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="settings.has_store_password ? '•••••••• saved — leave blank to keep' : 'Store password'"
                            />
                            <InputError class="mt-1" :message="form.errors.sslcommerz_store_password" />
                        </div>
                    </div>

                    <div
                        class="mt-5 flex items-start justify-between gap-4 rounded-xl p-4 ring-1"
                        :class="form.sslcommerz_sandbox ? 'bg-amber-50 ring-amber-200' : 'bg-emerald-50 ring-emerald-200'"
                    >
                        <div>
                            <p class="text-sm font-semibold" :class="form.sslcommerz_sandbox ? 'text-amber-900' : 'text-emerald-900'">
                                {{ form.sslcommerz_sandbox ? 'Test mode — no real money moves' : 'Live mode — customers are charged' }}
                            </p>
                            <p class="mt-1 text-xs" :class="form.sslcommerz_sandbox ? 'text-amber-800' : 'text-emerald-800'">
                                {{
                                    form.sslcommerz_sandbox
                                        ? 'Use sandbox credentials and test cards. Switch off once SSLCommerz approves your live store.'
                                        : 'Payments go to your live SSLCommerz store.'
                                }}
                            </p>
                        </div>
                        <ToggleSwitch v-model="form.sslcommerz_sandbox" label="Test mode" />
                    </div>

                    <div class="mt-5 rounded-xl border border-dashed border-gray-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Paste into SSLCommerz → IPN settings</p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="min-w-0 flex-1 truncate rounded-lg bg-gray-50 px-3 py-2 text-xs text-brand-navy">{{ callbackUrls.ipn }}</code>
                            <button type="button" class="shrink-0 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-brand-navy hover:bg-gray-50" @click="copy('ipn', callbackUrls.ipn)">
                                {{ copiedField === 'ipn' ? 'Copied' : 'Copy' }}
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">This lets SSLCommerz confirm payments even if a customer closes the page before returning to your shop.</p>
                    </div>
                </section>

                <section class="admin-card">
                    <h2 class="text-base font-semibold text-brand-navy">Checkout rules</h2>
                    <div class="mt-4 divide-y divide-gray-100">
                        <div class="flex items-start justify-between gap-4 pb-4">
                            <div>
                                <p class="text-sm font-medium text-brand-navy">Guest checkout</p>
                                <p class="mt-0.5 text-sm text-gray-500">Let customers order without creating an account.</p>
                            </div>
                            <ToggleSwitch v-model="form.guest_checkout" label="Guest checkout" />
                        </div>
                        <div class="flex items-start justify-between gap-4 pt-4">
                            <div>
                                <p class="text-sm font-medium text-brand-navy">Require a phone number</p>
                                <p class="mt-0.5 text-sm text-gray-500">Recommended for cash on delivery so you can confirm orders by phone. Always required for online payment.</p>
                            </div>
                            <ToggleSwitch v-model="form.require_phone" label="Require phone number" />
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Save payment settings</PrimaryButton>
                </div>
            </div>

            <aside class="h-fit rounded-2xl bg-brand-navy p-5 text-white lg:sticky lg:top-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Customer sees at checkout</p>
                <div class="mt-4 space-y-2">
                    <div v-if="form.payment_online_enabled" class="rounded-xl bg-white/10 px-3 py-3 ring-1 ring-white/20">
                        <div class="flex items-center gap-2.5">
                            <span class="h-4 w-4 shrink-0 rounded-full border-4 border-brand-orange bg-white" />
                            <span class="text-sm font-semibold">Pay online</span>
                            <span v-if="form.sslcommerz_sandbox" class="ml-auto rounded bg-amber-400/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-200">Test</span>
                        </div>
                        <p class="mt-1 pl-6 text-xs text-white/60">bKash, Nagad, Rocket or card</p>
                    </div>
                    <div v-if="form.payment_cod_enabled" class="rounded-xl bg-white/5 px-3 py-3 ring-1 ring-white/10">
                        <div class="flex items-center gap-2.5">
                            <span class="h-4 w-4 shrink-0 rounded-full border-2 border-white/40" :class="!form.payment_online_enabled ? 'border-4 border-brand-orange bg-white' : ''" />
                            <span class="text-sm font-semibold">Cash on delivery</span>
                        </div>
                        <p class="mt-1 pl-6 text-xs text-white/60">Pay when your order arrives</p>
                    </div>
                    <p v-if="!form.payment_cod_enabled && !form.payment_online_enabled" class="rounded-xl bg-red-500/20 px-3 py-2.5 text-sm text-red-100">
                        Customers can't check out without a payment method.
                    </p>
                </div>
                <p class="mt-4 border-t border-white/10 pt-4 text-xs text-white/60">
                    {{ form.require_phone || form.payment_online_enabled ? 'Phone number is required.' : 'Phone number is optional.' }}
                </p>
            </aside>
        </form>
    </AdminLayout>
</template>
