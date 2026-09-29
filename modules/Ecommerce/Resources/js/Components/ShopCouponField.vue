<script setup>
import { formatMoney } from '@/utils/formatMoney';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const cart = computed(() => page.props.shopCart ?? {});
const coupon = computed(() => cart.value.coupon ?? null);
const isApplied = computed(() => Boolean(coupon.value && !coupon.value.error));
const expanded = ref(false);
const removing = ref(false);

const form = useForm({ coupon: '' });

const error = computed(() => form.errors.coupon || page.props.errors?.coupon || coupon.value?.error || null);

const apply = () => {
    if (form.processing || !form.coupon.trim()) {
        return;
    }
    form.post(route('shop.cart.coupon.apply'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            expanded.value = false;
        },
    });
};

const remove = () => {
    removing.value = true;
    router.delete(route('shop.cart.coupon.remove'), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => (removing.value = false),
    });
};
</script>

<template>
    <div>
        <div
            v-if="isApplied"
            class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-emerald-300 bg-emerald-50 px-3 py-2.5"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a2 2 0 011.41.59l7 7a2 2 0 010 2.82l-7 7a2 2 0 01-2.82 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-emerald-800">{{ coupon.code }}</p>
                    <p class="text-xs text-emerald-700">You save {{ formatMoney(coupon.discount, cart.currency) }}</p>
                </div>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-emerald-800 hover:bg-emerald-100 disabled:opacity-50"
                :disabled="removing"
                @click="remove"
            >
                Remove
            </button>
        </div>

        <template v-else>
            <button
                v-if="!expanded && !error"
                type="button"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-teal-dark hover:underline"
                @click="expanded = true"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a2 2 0 011.41.59l7 7a2 2 0 010 2.82l-7 7a2 2 0 01-2.82 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Have a coupon code?
            </button>
            <div v-else class="flex gap-2">
                <input
                    v-model="form.coupon"
                    type="text"
                    placeholder="Enter coupon code"
                    autocomplete="off"
                    @keydown.enter.prevent="apply"
                    class="min-w-0 flex-1 rounded-xl border-gray-200 text-sm uppercase placeholder:normal-case focus:border-brand-teal focus:ring-brand-teal"
                    :class="error ? 'border-red-300' : ''"
                />
                <button
                    type="button"
                    class="shrink-0 rounded-xl bg-brand-navy px-4 text-sm font-semibold text-white transition hover:bg-brand-navy/90 disabled:opacity-50"
                    :disabled="form.processing || !form.coupon.trim()"
                    @click="apply"
                >
                    {{ form.processing ? 'Checking…' : 'Apply' }}
                </button>
            </div>
            <p v-if="error" class="mt-1.5 flex items-center gap-2 text-xs text-red-600">
                <span>{{ coupon?.error ? `${coupon.code}: ${error}` : error }}</span>
                <button v-if="coupon?.error" type="button" class="font-semibold underline" :disabled="removing" @click="remove">Remove</button>
            </p>
        </template>
    </div>
</template>
