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
    ownerEmail: { type: String, default: null },
    smsLive: { type: Boolean, default: false },
    hasOrders: { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    notify_customer_email: Boolean(props.settings.notify_customer_email),
    notify_customer_sms: Boolean(props.settings.notify_customer_sms),
    notify_staff_email: Boolean(props.settings.notify_staff_email),
    notify_staff_sms: Boolean(props.settings.notify_staff_sms),
    staff_notification_email: props.settings.staff_notification_email ?? '',
    staff_notification_phone: props.settings.staff_notification_phone ?? '',
});

const previews = [
    { key: 'customer-placed', label: 'Order received', audience: 'Customer' },
    { key: 'customer-confirmed', label: 'Confirmed', audience: 'Customer' },
    { key: 'customer-cancelled', label: 'Cancelled', audience: 'Customer' },
    { key: 'staff-placed', label: 'New order alert', audience: 'You' },
];
const activePreview = ref(previews[0].key);
const previewUrl = computed(() => route('ecommerce.notifications.preview', activePreview.value));

const staffEmailFallback = computed(() => (props.settings.staff_notification_email ? null : props.ownerEmail));

const usesSms = computed(() => form.notify_customer_sms || form.notify_staff_sms);

const save = () => {
    form.put(route('ecommerce.notifications.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Order Notifications" />

    <AdminLayout title="Order Notifications">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,34rem)]" @submit.prevent="save">
            <div class="space-y-6">
                <section class="admin-card">
                    <h2 class="text-base font-semibold text-brand-navy">Tell your customers</h2>
                    <p class="mt-1 text-sm text-gray-500">Sent when an order is placed, confirmed or cancelled.</p>

                    <div class="mt-5 divide-y divide-gray-100">
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800">Email</p>
                                <p class="text-xs text-gray-500">Only when the customer gives an email address at checkout.</p>
                            </div>
                            <ToggleSwitch v-model="form.notify_customer_email" label="Email customers" />
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800">SMS</p>
                                <p class="text-xs text-gray-500">A short text with the order number and a tracking link.</p>
                            </div>
                            <ToggleSwitch v-model="form.notify_customer_sms" label="Text customers" />
                        </div>
                    </div>
                </section>

                <section class="admin-card">
                    <h2 class="text-base font-semibold text-brand-navy">Tell your team about new orders</h2>
                    <p class="mt-1 text-sm text-gray-500">An in-app alert always goes to the shop owner. Add email or SMS so nobody misses an order.</p>

                    <div class="mt-5 divide-y divide-gray-100">
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Email</p>
                                    <p class="text-xs text-gray-500">Full order details with a link to open it in admin.</p>
                                </div>
                                <ToggleSwitch v-model="form.notify_staff_email" label="Email the team" />
                            </div>
                            <div v-if="form.notify_staff_email" class="mt-3 max-w-md">
                                <InputLabel value="Send to" />
                                <TextInput
                                    v-model="form.staff_notification_email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    :placeholder="ownerEmail || 'orders@yourshop.com'"
                                />
                                <p v-if="!form.staff_notification_email && staffEmailFallback" class="mt-1 text-xs text-gray-500">
                                    Leave empty to use the owner's email, {{ staffEmailFallback }}.
                                </p>
                                <InputError class="mt-1" :message="form.errors.staff_notification_email" />
                            </div>
                        </div>
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">SMS</p>
                                    <p class="text-xs text-gray-500">Order number, total and the customer's phone.</p>
                                </div>
                                <ToggleSwitch v-model="form.notify_staff_sms" label="Text the team" />
                            </div>
                            <div v-if="form.notify_staff_sms" class="mt-3 max-w-md">
                                <InputLabel value="Mobile number" />
                                <TextInput v-model="form.staff_notification_phone" type="tel" class="mt-1 block w-full" placeholder="01711-223344" />
                                <InputError class="mt-1" :message="form.errors.staff_notification_phone" />
                            </div>
                        </div>
                    </div>
                </section>

                <div v-if="usesSms && !smsLive" class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p>
                        SMS is not connected yet, so texts are only written to the log. Ask the platform admin to set
                        <code class="rounded bg-amber-100 px-1">SMS_DRIVER=bulksmsbd</code> with an API key.
                    </p>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Save notification settings</PrimaryButton>
                </div>
            </div>

            <aside class="h-fit overflow-hidden rounded-2xl border border-gray-200 bg-white xl:sticky xl:top-4">
                <div class="border-b border-gray-100 px-4 pt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Email preview</p>
                        <a :href="previewUrl" target="_blank" rel="noopener" class="text-xs font-medium text-brand-teal-dark hover:underline">Open full size ↗</a>
                    </div>
                    <div class="-mb-px mt-3 flex gap-1 overflow-x-auto">
                        <button
                            v-for="preview in previews"
                            :key="preview.key"
                            type="button"
                            class="shrink-0 border-b-2 px-3 pb-2.5 text-left text-sm transition-colors"
                            :class="activePreview === preview.key ? 'border-brand-orange font-medium text-brand-navy' : 'border-transparent text-gray-500 hover:text-brand-navy'"
                            @click="activePreview = preview.key"
                        >
                            <span class="block text-[10px] uppercase tracking-wide text-gray-400">{{ preview.audience }}</span>
                            {{ preview.label }}
                        </button>
                    </div>
                </div>
                <iframe :key="previewUrl" :src="previewUrl" title="Email preview" class="h-[36rem] w-full bg-gray-100" />
                <p class="border-t border-gray-100 px-4 py-2.5 text-xs text-gray-500">
                    {{ hasOrders ? 'Showing your latest order.' : 'Showing a sample order until your first real order arrives.' }}
                </p>
            </aside>
        </form>
    </AdminLayout>
</template>
