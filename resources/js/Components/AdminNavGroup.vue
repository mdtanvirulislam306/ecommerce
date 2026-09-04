<script setup>
import AdminNavSubLink from '@/Components/AdminNavSubLink.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    children: {
        type: Array,
        required: true,
        // { label, href, active }
    },
});

const open = ref(false);

const isChildActive = computed(() =>
    props.children.some((child) => child.active),
);

watch(
    isChildActive,
    (active) => {
        if (active) {
            open.value = true;
        }
    },
    { immediate: true },
);

const toggle = () => {
    open.value = !open.value;
};
</script>

<template>
    <div>
        <button
            type="button"
            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
            :class="
                isChildActive
                    ? 'text-brand-navy'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-brand-navy'
            "
            @click="toggle"
        >
            <span class="flex items-center gap-3">
                <span
                    class="flex h-5 w-5 shrink-0 items-center justify-center"
                    :class="isChildActive ? 'text-brand-teal-dark' : 'text-gray-400'"
                >
                    <slot name="icon" />
                </span>
                {{ label }}
            </span>
            <svg
                class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                :class="open ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div v-show="open" class="mt-1 space-y-0.5 pl-8">
            <AdminNavSubLink
                v-for="child in children"
                :key="child.href"
                :href="child.href"
                :active="child.active"
            >
                {{ child.label }}
            </AdminNavSubLink>
        </div>
    </div>
</template>
