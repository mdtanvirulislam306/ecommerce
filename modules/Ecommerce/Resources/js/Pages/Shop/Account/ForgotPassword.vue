<script setup>
import ShopAuthShell from '../../../Components/ShopAuthShell.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({ email: '' });

const submit = () => form.post(route('shop.account.password.email'));
</script>

<template>
    <Head title="Forgot password" />

    <ShopAuthShell title="Forgot your password?" subtitle="Enter your email and we'll send you a link to choose a new one.">
        <div v-if="status" class="mb-6 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-100">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <label class="block">
                <span class="text-xs font-medium text-gray-600">Email</span>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy placeholder:text-gray-400 focus:border-brand-teal focus:ring-brand-teal"
                    placeholder="you@example.com"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </label>

            <button
                type="submit"
                class="w-full rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                :disabled="form.processing"
            >
                Send reset link
            </button>
        </form>

        <p class="mt-8 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
            Remembered it?
            <Link :href="route('shop.account.login')" class="font-semibold text-brand-orange hover:underline">Back to sign in</Link>
        </p>
    </ShopAuthShell>
</template>
