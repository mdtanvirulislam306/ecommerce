<script setup>
import ShopAuthShell from '../../../Components/ShopAuthShell.vue';
import ShopPasswordInput from '../../../Components/ShopPasswordInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
    returningToCheckout: { type: Boolean, default: false },
});

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('shop.account.login.store'), {
        onFinish: () => form.reset('password'),
    });
};

const inputClass = 'mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy placeholder:text-gray-400 focus:border-brand-teal focus:ring-brand-teal';
</script>

<template>
    <Head title="Sign in" />

    <ShopAuthShell title="Welcome back" subtitle="Sign in to track orders and check out faster.">
        <div v-if="status" class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-100">
            {{ status }}
        </div>
        <div v-else-if="returningToCheckout" class="mb-6 flex items-center gap-3 rounded-xl bg-brand-orange/10 px-4 py-3 text-sm text-brand-navy ring-1 ring-brand-orange/20">
            <svg class="h-5 w-5 shrink-0 text-brand-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 10-8 0v4M5 9h14l-1.2 11.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 9z" />
            </svg>
            Your cart is saved. Sign in and we'll take you straight back to checkout.
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <label class="block">
                <span class="text-xs font-medium text-gray-600">Email</span>
                <input v-model="form.email" type="email" autocomplete="email" required autofocus :class="inputClass" placeholder="you@example.com" />
                <InputError class="mt-1" :message="form.errors.email" />
            </label>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="text-xs font-medium text-gray-600">Password</label>
                    <Link :href="route('shop.account.password.request')" class="text-xs font-medium text-brand-teal-dark hover:underline">
                        Forgot password?
                    </Link>
                </div>
                <ShopPasswordInput id="password" v-model="form.password" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-brand-orange focus:ring-brand-orange" />
                Keep me signed in
            </label>

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
                Sign in
            </button>
        </form>

        <p class="mt-8 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
            New here?
            <Link :href="route('shop.account.register')" class="font-semibold text-brand-orange hover:underline">Create an account</Link>
        </p>
    </ShopAuthShell>
</template>
