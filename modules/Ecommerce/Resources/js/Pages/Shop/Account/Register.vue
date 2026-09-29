<script setup>
import ShopAuthShell from '../../../Components/ShopAuthShell.vue';
import ShopPasswordInput from '../../../Components/ShopPasswordInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    returningToCheckout: { type: Boolean, default: false },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('shop.account.register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = 'mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy placeholder:text-gray-400 focus:border-brand-teal focus:ring-brand-teal';
</script>

<template>
    <Head title="Create account" />

    <ShopAuthShell title="Create your account" subtitle="It takes less than a minute.">
        <div v-if="returningToCheckout" class="mb-6 rounded-xl bg-brand-orange/10 px-4 py-3 text-sm text-brand-navy ring-1 ring-brand-orange/20">
            Your cart is saved — you'll go straight back to checkout after this.
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <label class="block">
                <span class="text-xs font-medium text-gray-600">Full name</span>
                <input v-model="form.name" type="text" autocomplete="name" required autofocus :class="inputClass" placeholder="Your name" />
                <InputError class="mt-1" :message="form.errors.name" />
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-medium text-gray-600">Email</span>
                    <input v-model="form.email" type="email" autocomplete="email" required :class="inputClass" placeholder="you@example.com" />
                    <InputError class="mt-1" :message="form.errors.email" />
                </label>
                <label class="block">
                    <span class="text-xs font-medium text-gray-600">Phone <span class="text-gray-400">(for delivery)</span></span>
                    <input v-model="form.phone" type="tel" inputmode="tel" autocomplete="tel" :class="inputClass" placeholder="01XXXXXXXXX" />
                    <InputError class="mt-1" :message="form.errors.phone" />
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="text-xs font-medium text-gray-600">Password</label>
                    <ShopPasswordInput id="password" v-model="form.password" autocomplete="new-password" placeholder="At least 8 characters" />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>
                <div>
                    <label for="password_confirmation" class="text-xs font-medium text-gray-600">Confirm password</label>
                    <ShopPasswordInput id="password_confirmation" v-model="form.password_confirmation" autocomplete="new-password" />
                </div>
            </div>

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
                Create account
            </button>
        </form>

        <p class="mt-8 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
            Already have an account?
            <Link :href="route('shop.account.login')" class="font-semibold text-brand-orange hover:underline">Sign in</Link>
        </p>
    </ShopAuthShell>
</template>
