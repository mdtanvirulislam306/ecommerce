<script setup>
import PlatformShell from '../../Components/PlatformShell.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import { formatMoney } from '@/utils/formatMoney';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
    serverMailer: { type: String, default: 'log' },
});

const page = usePage();

const form = useForm({
    platform_name: props.settings.platform_name,
    support_email: props.settings.support_email,
    default_plan_id: props.settings.default_plan_id,
    mail_mailer: props.settings.mail_mailer,
    mail_host: props.settings.mail_host,
    mail_port: props.settings.mail_port,
    mail_encryption: props.settings.mail_encryption,
    mail_username: props.settings.mail_username,
    mail_password: '',
    mail_from_address: props.settings.mail_from_address,
    mail_from_name: props.settings.mail_from_name,
    sslcommerz_store_id: props.settings.sslcommerz_store_id,
    sslcommerz_store_password: '',
    sslcommerz_sandbox: props.settings.sslcommerz_sandbox,
    maintenance_enabled: props.settings.maintenance_enabled,
    maintenance_message: props.settings.maintenance_message,
});

const testForm = useForm({ email: page.props.auth?.user?.email ?? '' });

const sections = [
    { id: 'general', label: 'General' },
    { id: 'new-shops', label: 'New shops' },
    { id: 'email', label: 'Email' },
    { id: 'payments', label: 'Subscription payments' },
    { id: 'maintenance', label: 'Maintenance' },
];

const usesSmtp = computed(() => form.mail_mailer === 'smtp');

const submit = () =>
    form.put(route('platform.settings.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('mail_password', 'sslcommerz_store_password'),
    });

const sendTest = () => testForm.post(route('platform.settings.test-email'), { preserveScroll: true });

const planLabel = (plan) => `${plan.name} · ${plan.price_monthly ? `${formatMoney(plan.price_monthly / 100, plan.currency, { decimals: 0 })}/mo` : 'Free'}`;
</script>

<template>
    <PlatformShell title="Settings">
        <div
            v-if="settings.maintenance_enabled"
            class="flex items-center gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200"
            role="status"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            Maintenance mode is on. Shops show a "back soon" page to everyone except platform admins.
        </div>

        <div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
            <nav class="hidden lg:block">
                <ul class="sticky top-6 space-y-1 text-sm">
                    <li v-for="section in sections" :key="section.id">
                        <a :href="`#${section.id}`" class="block rounded-lg px-3 py-2 font-medium text-gray-500 transition hover:bg-white hover:text-brand-navy">{{ section.label }}</a>
                    </li>
                </ul>
            </nav>

            <form class="space-y-6" @submit.prevent="submit">
                <section id="general" class="admin-card scroll-mt-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">General</h2>
                        <p class="text-xs text-gray-500">How the platform introduces itself in emails and on the maintenance page.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="platform-name" value="Platform name" />
                            <TextInput id="platform-name" v-model="form.platform_name" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="form.errors.platform_name" />
                        </div>
                        <div>
                            <InputLabel for="support-email" value="Support email" />
                            <TextInput id="support-email" v-model="form.support_email" type="email" class="mt-1 block w-full" placeholder="support@example.com" />
                            <p class="mt-1 text-xs text-gray-400">Shown to shop owners who need help.</p>
                            <InputError class="mt-1" :message="form.errors.support_email" />
                        </div>
                    </div>
                </section>

                <section id="new-shops" class="admin-card scroll-mt-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">New shops</h2>
                        <p class="text-xs text-gray-500">The plan picked for you when you provision a shop.</p>
                    </div>
                    <div class="sm:w-96">
                        <InputLabel for="default-plan" value="Default plan" />
                        <select
                            id="default-plan"
                            v-model="form.default_plan_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                        >
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ planLabel(plan) }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.default_plan_id" />
                    </div>
                </section>

                <section id="email" class="admin-card scroll-mt-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Email</h2>
                        <p class="text-xs text-gray-500">Staff invitations, password resets and order emails go out through this account.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label
                            v-for="option in [
                                { value: 'server', title: 'Server settings', body: `Use the mailer configured on the server (currently “${serverMailer}”).` },
                                { value: 'smtp', title: 'SMTP account', body: 'Send through your own mail provider, like Gmail, Zoho or Mailgun.' },
                            ]"
                            :key="option.value"
                            class="flex cursor-pointer gap-3 rounded-xl border px-4 py-3 transition"
                            :class="form.mail_mailer === option.value ? 'border-brand-teal bg-brand-teal/5 ring-1 ring-brand-teal/40' : 'border-gray-200 hover:bg-gray-50'"
                        >
                            <input v-model="form.mail_mailer" type="radio" :value="option.value" class="mt-1 border-gray-300 text-brand-teal focus:ring-brand-teal" />
                            <span>
                                <span class="block text-sm font-semibold text-brand-navy">{{ option.title }}</span>
                                <span class="block text-xs text-gray-500">{{ option.body }}</span>
                            </span>
                        </label>
                    </div>

                    <div v-if="usesSmtp" class="grid gap-4 rounded-xl bg-gray-50 p-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <InputLabel for="mail-host" value="SMTP server" />
                            <TextInput id="mail-host" v-model="form.mail_host" class="mt-1 block w-full" placeholder="smtp.gmail.com" />
                            <InputError class="mt-1" :message="form.errors.mail_host" />
                        </div>
                        <div class="sm:col-span-1">
                            <InputLabel for="mail-port" value="Port" />
                            <TextInput id="mail-port" v-model="form.mail_port" type="number" class="mt-1 block w-full" placeholder="587" />
                            <InputError class="mt-1" :message="form.errors.mail_port" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="mail-encryption" value="Security" />
                            <select
                                id="mail-encryption"
                                v-model="form.mail_encryption"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            >
                                <option value="tls">STARTTLS (port 587)</option>
                                <option value="ssl">SSL (port 465)</option>
                                <option value="none">None</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.mail_encryption" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="mail-username" value="Username" />
                            <TextInput id="mail-username" v-model="form.mail_username" class="mt-1 block w-full" autocomplete="off" />
                            <InputError class="mt-1" :message="form.errors.mail_username" />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel for="mail-password" value="Password" />
                            <TextInput
                                id="mail-password"
                                v-model="form.mail_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="settings.has_mail_password ? 'Saved — leave blank to keep it' : ''"
                            />
                            <InputError class="mt-1" :message="form.errors.mail_password" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="mail-from-address" value="Send from address" />
                            <TextInput id="mail-from-address" v-model="form.mail_from_address" type="email" class="mt-1 block w-full" placeholder="no-reply@example.com" />
                            <InputError class="mt-1" :message="form.errors.mail_from_address" />
                        </div>
                        <div>
                            <InputLabel for="mail-from-name" value="Send from name" />
                            <TextInput id="mail-from-name" v-model="form.mail_from_name" class="mt-1 block w-full" :placeholder="form.platform_name" />
                            <InputError class="mt-1" :message="form.errors.mail_from_name" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <InputLabel for="test-email" value="Send a test email" />
                            <TextInput id="test-email" v-model="testForm.email" type="email" class="mt-1 block w-full" placeholder="you@example.com" />
                            <InputError class="mt-1" :message="testForm.errors.email || page.props.errors?.test_email" />
                        </div>
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-brand-navy ring-1 ring-gray-200 transition hover:bg-gray-50 disabled:opacity-50"
                            :disabled="testForm.processing || !testForm.email"
                            @click="sendTest"
                        >
                            {{ testForm.processing ? 'Sending…' : 'Send test' }}
                        </button>
                    </div>
                    <p class="-mt-2 text-xs text-gray-400">Save your changes first — the test uses the saved settings.</p>
                </section>

                <section id="payments" class="admin-card scroll-mt-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Subscription payments</h2>
                        <p class="text-xs text-gray-500">
                            The SSLCommerz account shop owners pay their plan into. This is the platform's own account — each shop sets up its own for customer checkout.
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="ssl-store-id" value="Store ID" />
                            <TextInput id="ssl-store-id" v-model="form.sslcommerz_store_id" class="mt-1 block w-full" autocomplete="off" />
                            <InputError class="mt-1" :message="form.errors.sslcommerz_store_id" />
                        </div>
                        <div>
                            <InputLabel for="ssl-store-password" value="Store password" />
                            <TextInput
                                id="ssl-store-password"
                                v-model="form.sslcommerz_store_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="settings.has_sslcommerz_store_password ? 'Saved — leave blank to keep it' : ''"
                            />
                            <InputError class="mt-1" :message="form.errors.sslcommerz_store_password" />
                        </div>
                    </div>
                    <ToggleSwitch
                        v-model="form.sslcommerz_sandbox"
                        label="Sandbox mode"
                        description="Use SSLCommerz test credentials. Turn off when you are ready to take real payments."
                    />
                </section>

                <section id="maintenance" class="admin-card scroll-mt-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-brand-navy">Maintenance</h2>
                        <p class="text-xs text-gray-500">Take every shop offline while you upgrade the platform.</p>
                    </div>
                    <ToggleSwitch
                        v-model="form.maintenance_enabled"
                        label="Maintenance mode"
                        description="Shop admins and storefronts show a “back soon” page. Platform admins keep full access."
                    />
                    <div>
                        <InputLabel for="maintenance-message" value="Message for visitors" />
                        <textarea
                            id="maintenance-message"
                            v-model="form.maintenance_message"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                            placeholder="We are making some improvements and will be back shortly."
                        />
                        <InputError class="mt-1" :message="form.errors.maintenance_message" />
                    </div>
                </section>

                <div class="sticky bottom-4 z-10 flex items-center justify-end gap-3 rounded-2xl border border-gray-100 bg-white/95 px-5 py-3 shadow-lg backdrop-blur">
                    <p v-if="form.isDirty" class="mr-auto text-sm text-gray-500">You have unsaved changes.</p>
                    <button
                        type="submit"
                        class="rounded-xl bg-brand-orange px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-orange-dark disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving…' : 'Save settings' }}
                    </button>
                </div>
            </form>
        </div>
    </PlatformShell>
</template>
