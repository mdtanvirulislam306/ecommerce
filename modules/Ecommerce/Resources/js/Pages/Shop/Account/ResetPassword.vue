<script setup>
import ShopAuthShell from '../../../Components/ShopAuthShell.vue';
import ShopPasswordInput from '../../../Components/ShopPasswordInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('shop.account.password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Choose a new password" />

    <ShopAuthShell title="Choose a new password" subtitle="Pick something you haven't used here before.">
        <form class="space-y-5" @submit.prevent="submit">
            <label class="block">
                <span class="text-xs font-medium text-gray-600">Email</span>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    class="mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy focus:border-brand-teal focus:ring-brand-teal"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </label>
            <div>
                <label for="password" class="text-xs font-medium text-gray-600">New password</label>
                <ShopPasswordInput id="password" v-model="form.password" autocomplete="new-password" placeholder="At least 8 characters" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>
            <div>
                <label for="password_confirmation" class="text-xs font-medium text-gray-600">Confirm new password</label>
                <ShopPasswordInput id="password_confirmation" v-model="form.password_confirmation" autocomplete="new-password" />
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                :disabled="form.processing"
            >
                Save new password
            </button>
        </form>
    </ShopAuthShell>
</template>
