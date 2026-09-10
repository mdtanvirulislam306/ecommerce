<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const items = computed(() => props.widget.settings?.items ?? []);
const openIndex = ref(0);
</script>

<template>
    <div class="space-y-4">
        <h2 v-if="widget.settings?.heading" class="text-2xl font-semibold text-brand-navy">
            {{ widget.settings.heading }}
        </h2>
        <div class="divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div v-for="(item, index) in items" :key="index">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left text-sm font-medium text-brand-navy"
                    @click="openIndex = openIndex === index ? -1 : index"
                >
                    <span>{{ item.question }}</span>
                    <span class="text-gray-400">{{ openIndex === index ? '−' : '+' }}</span>
                </button>
                <p v-if="openIndex === index" class="px-5 pb-4 text-sm leading-6 text-gray-600">
                    {{ item.answer }}
                </p>
            </div>
        </div>
    </div>
</template>
