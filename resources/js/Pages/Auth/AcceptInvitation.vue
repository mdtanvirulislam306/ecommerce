<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    token: { type: String, required: true },
    shopName: { type: String, required: true },
    invitation: { type: Object, default: null },
});

const form = useForm({
    name: props.invitation?.name ?? '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const firstName = computed(() => (props.invitation?.name ?? '').split(' ')[0]);

const submit = () => {
    form.post(route('invitation.accept', props.token), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = 'mt-1 block w-full rounded-xl border-gray-200 text-sm text-brand-navy placeholder:text-gray-400 focus:border-brand-teal focus:ring-brand-teal';
</script>

<template>
    <Head :title="invitation ? `Join ${shopName}` : 'Invitation unavailable'" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-50 via-white to-brand-teal/5 px-4 py-10">
        <div v-if="invitation" class="grid w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-brand-navy/5 ring-1 ring-black/5 md:grid-cols-[1fr_1.15fr]">
            <aside class="relative flex flex-col justify-between overflow-hidden bg-brand-navy p-8 text-white md:p-10">
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-brand-teal/20 blur-2xl" />
                <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-brand-orange/20 blur-2xl" />

                <div class="relative">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-teal" />
                        Team invitation
                    </span>
                    <h1 class="mt-6 text-3xl font-semibold leading-tight tracking-tight">
                        Join <span class="text-brand-orange">{{ shopName }}</span>
                    </h1>
                    <p class="mt-3 text-sm leading-relaxed text-white/70">
                        <template v-if="invitation.inviter">{{ invitation.inviter }} invited you to help run the shop.</template>
                        <template v-else>You've been invited to help run the shop.</template>
                        Set a password and you're in.
                    </p>
                </div>

                <div v-if="invitation.roles.length" class="relative mt-10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-white/50">Your access</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span v-for="role in invitation.roles" :key="role" class="rounded-full bg-white/10 px-3 py-1 text-sm font-medium ring-1 ring-white/20">
                            {{ role }}
                        </span>
                    </div>
                </div>
            </aside>

            <form class="p-8 md:p-10" @submit.prevent="submit">
                <h2 class="text-xl font-semibold text-brand-navy">Welcome, {{ firstName }}</h2>
                <p class="mt-1 text-sm text-gray-500">Confirm your name and choose a password.</p>

                <div class="mt-8 space-y-5">
                    <div>
                        <span class="text-xs font-medium text-gray-600">Email</span>
                        <p class="mt-1 flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2.5 text-sm text-gray-600 ring-1 ring-gray-100">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            {{ invitation.email }}
                        </p>
                    </div>

                    <label class="block">
                        <span class="text-xs font-medium text-gray-600">Full name</span>
                        <input v-model="form.name" type="text" autocomplete="name" required :class="inputClass" />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </label>

                    <div>
                        <label for="password" class="text-xs font-medium text-gray-600">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                autofocus
                                :class="[inputClass, 'pr-16']"
                                placeholder="At least 8 characters"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-2 my-auto h-7 rounded-lg px-2 text-xs font-semibold text-gray-500 hover:bg-gray-100 hover:text-brand-navy"
                                @click="showPassword = !showPassword"
                            >
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <label class="block">
                        <span class="text-xs font-medium text-gray-600">Confirm password</span>
                        <input
                            v-model="form.password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            required
                            :class="inputClass"
                        />
                    </label>
                </div>

                <button
                    type="submit"
                    class="mt-8 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                    </svg>
                    Activate my account
                </button>
            </form>
        </div>

        <div v-else class="w-full max-w-md rounded-3xl bg-white p-10 text-center shadow-xl shadow-brand-navy/5 ring-1 ring-black/5">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <h1 class="mt-5 text-xl font-semibold text-brand-navy">This invitation can't be used</h1>
            <p class="mt-2 text-sm leading-relaxed text-gray-500">
                It may have expired, been replaced by a newer email, or already been accepted.
                Ask the {{ shopName }} owner to send you a fresh invitation.
            </p>
            <Link
                :href="route('login')"
                class="mt-8 inline-flex items-center justify-center rounded-xl bg-brand-navy px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-navy-dark"
            >
                Go to sign in
            </Link>
        </div>
    </div>
</template>
