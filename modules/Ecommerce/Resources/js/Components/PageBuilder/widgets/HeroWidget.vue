<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
    editing: { type: Boolean, default: false },
});

const overlay = computed(() => {
    const value = Number(props.widget.settings?.overlay);
    return Number.isFinite(value) ? Math.min(80, Math.max(0, value)) / 100 : 0.4;
});

const alignClass = computed(() => ({
    left: 'items-start text-left',
    center: 'items-center text-center',
    right: 'items-end text-right',
}[props.widget.settings?.align] ?? 'items-start text-left'));
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl bg-brand-navy px-8 py-16 text-white sm:px-12"
        :style="widget.settings?.image_url
            ? { backgroundImage: `url(${widget.settings.image_url})`, backgroundSize: 'cover', backgroundPosition: 'center' }
            : undefined"
    >
        <div class="absolute inset-0 bg-brand-navy" :style="{ opacity: overlay }" />
        <div class="relative z-10 flex flex-col gap-4" :class="alignClass">
            <h2 class="max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">
                {{ widget.settings?.title }}
            </h2>
            <p v-if="widget.settings?.subtitle" class="max-w-xl text-base text-white/85">
                {{ widget.settings.subtitle }}
            </p>
            <a
                v-if="widget.settings?.cta_label"
                :href="editing ? undefined : (widget.settings?.cta_url || '/shop')"
                class="inline-flex items-center rounded-lg bg-brand-orange px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-orange-dark"
                @click="editing ? $event.preventDefault() : null"
            >
                {{ widget.settings.cta_label }}
            </a>
        </div>
    </div>
</template>
