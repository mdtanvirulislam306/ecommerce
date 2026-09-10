<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const items = computed(() => props.widget.settings?.items ?? []);
</script>

<template>
    <div class="space-y-4">
        <h2 v-if="widget.settings?.heading" class="text-2xl font-semibold text-brand-navy">
            {{ widget.settings.heading }}
        </h2>
        <div class="grid gap-4 md:grid-cols-2">
            <blockquote
                v-for="(item, index) in items"
                :key="index"
                class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <p class="text-sm leading-6 text-gray-700">“{{ item.quote }}”</p>
                <footer class="mt-4 flex items-center gap-3">
                    <img
                        v-if="item.avatar_url"
                        :src="item.avatar_url"
                        alt=""
                        class="h-10 w-10 rounded-full object-cover"
                    />
                    <span
                        v-else
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-orange/10 text-sm font-semibold text-brand-orange"
                    >
                        {{ (item.name || 'C').charAt(0) }}
                    </span>
                    <span>
                        <span class="block text-sm font-medium text-brand-navy">{{ item.name }}</span>
                        <span class="text-xs text-gray-500">{{ item.role }}</span>
                    </span>
                </footer>
            </blockquote>
        </div>
    </div>
</template>
