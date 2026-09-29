<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const page = usePage();
const open = ref(false);
const order = ref(null);
const canvasRef = ref(null);

/** @type {number|null} */
let confettiFrame = null;
/** @type {ReturnType<typeof setTimeout>|null} */
let confettiTimer = null;

const currency = computed(() => order.value?.currency || 'BDT');
const items = computed(() => order.value?.items ?? []);

const money = (amount) => `${currency.value} ${Number(amount || 0).toFixed(2)}`;

const close = () => {
    open.value = false;
    order.value = null;
    stopConfetti();
    document.body.classList.remove('overflow-hidden');
};

const stopConfetti = () => {
    if (confettiFrame) {
        cancelAnimationFrame(confettiFrame);
        confettiFrame = null;
    }
    if (confettiTimer) {
        clearTimeout(confettiTimer);
        confettiTimer = null;
    }
    const canvas = canvasRef.value;
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx?.clearRect(0, 0, canvas.width, canvas.height);
    }
};

const launchConfetti = async () => {
    await nextTick();
    const canvas = canvasRef.value;
    if (!canvas) {
        return;
    }

    const resize = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    };
    resize();

    const colors = ['#F27D42', '#2C4B60', '#2A9D8F', '#E9C46A', '#E76F51', '#ffffff'];
    const pieces = Array.from({ length: 140 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height * -0.4,
        w: 6 + Math.random() * 8,
        h: 8 + Math.random() * 10,
        vx: -3 + Math.random() * 6,
        vy: 2 + Math.random() * 5,
        rot: Math.random() * Math.PI,
        vr: -0.2 + Math.random() * 0.4,
        color: colors[Math.floor(Math.random() * colors.length)],
    }));

    const started = performance.now();
    const duration = 4200;

    const tick = (now) => {
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const progress = Math.min(1, (now - started) / duration);
        const fade = progress > 0.7 ? 1 - (progress - 0.7) / 0.3 : 1;

        pieces.forEach((p) => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.08;
            p.rot += p.vr;
            ctx.save();
            ctx.globalAlpha = fade;
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });

        if (progress < 1 && open.value) {
            confettiFrame = requestAnimationFrame(tick);
        } else {
            confettiFrame = null;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    };

    window.addEventListener('resize', resize, { once: true });
    confettiFrame = requestAnimationFrame(tick);
    confettiTimer = setTimeout(() => stopConfetti(), duration + 200);
};

watch(
    () => page.props.flash?.order_placed,
    (payload) => {
        if (!payload) {
            return;
        }
        order.value = payload;
        open.value = true;
        document.body.classList.add('overflow-hidden');
        launchConfetti();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    stopConfetti();
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <Teleport to="body">
        <div v-if="open && order" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <canvas ref="canvasRef" class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true" />
            <button type="button" class="absolute inset-0 bg-brand-navy/45" aria-label="Close thank you" @click="close" />

            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                <div class="bg-gradient-to-br from-brand-orange/15 via-white to-brand-teal/15 px-6 pb-4 pt-8 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-brand-orange">Thank you</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-brand-navy">Order placed!</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        We’ll confirm soon. Pay when you receive the goods.
                    </p>
                </div>

                <div class="space-y-3 px-6 py-5 text-sm">
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500">Order</span>
                            <span class="font-semibold text-brand-navy">{{ order.number }}</span>
                        </div>
                        <div class="mt-1.5 flex justify-between gap-3">
                            <span class="text-gray-500">Payment</span>
                            <span class="font-medium text-brand-navy">{{ order.payment_method_label }}</span>
                        </div>
                        <div v-if="Number(order.discount_total) > 0" class="mt-1.5 flex justify-between gap-3">
                            <span class="text-gray-500">Coupon {{ order.coupon_code }}</span>
                            <span class="font-medium text-emerald-600">−{{ money(order.discount_total) }}</span>
                        </div>
                        <div class="mt-1.5 flex justify-between gap-3">
                            <span class="text-gray-500">Delivery</span>
                            <span class="font-medium" :class="Number(order.shipping_fee) === 0 ? 'text-emerald-600' : 'text-brand-navy'">
                                {{ Number(order.shipping_fee) === 0 ? 'FREE' : money(order.shipping_fee) }}
                            </span>
                        </div>
                        <div class="mt-1.5 flex justify-between gap-3 border-t border-gray-200 pt-1.5">
                            <span class="text-gray-500">Total to pay</span>
                            <span class="font-semibold text-brand-navy">{{ money(order.grand_total) }}</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Customer</p>
                        <p class="mt-1 font-medium text-brand-navy">{{ order.customer_name }}</p>
                        <p v-if="order.customer_phone" class="text-gray-600">{{ order.customer_phone }}</p>
                        <p v-if="order.shipping_address" class="mt-1 whitespace-pre-line text-gray-500">{{ order.shipping_address }}</p>
                    </div>

                    <div v-if="items.length" class="max-h-40 space-y-2 overflow-y-auto rounded-xl border border-gray-100 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Items</p>
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-start justify-between gap-3 border-b border-gray-50 pb-2 last:border-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-brand-navy">{{ item.name }}</p>
                                <p class="text-xs text-gray-400">× {{ Number(item.quantity) }}</p>
                            </div>
                            <p class="shrink-0 font-medium text-brand-navy">{{ money(item.line_total) }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 border-t border-gray-100 px-6 py-4">
                    <Link
                        v-if="order.tracking_url"
                        :href="order.tracking_url"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-brand-navy/15 py-3 text-sm font-semibold text-brand-navy transition hover:bg-brand-navy/5"
                        @click="close"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Track your order
                    </Link>
                    <button
                        type="button"
                        class="w-full rounded-xl bg-brand-orange py-3 text-sm font-semibold text-white shadow-sm shadow-brand-orange/30 transition hover:bg-brand-orange-dark"
                        @click="close"
                    >
                        Continue shopping
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
