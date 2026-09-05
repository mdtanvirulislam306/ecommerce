<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

defineProps({
    csvUrl: { type: String, required: true },
    pdfUrl: { type: String, required: true },
    printUrl: { type: String, required: true },
});

const open = ref(false);
const root = ref(null);

const onClickOutside = (event) => {
    if (root.value && !root.value.contains(event.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<template>
    <div ref="root" class="relative">
        <button type="button" class="admin-export-trigger" @click="open = !open">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export
        </button>
        <div v-if="open" class="admin-export-menu">
            <a :href="csvUrl" class="admin-export-menu__item" @click="open = false">Download CSV</a>
            <a :href="pdfUrl" target="_blank" rel="noopener" class="admin-export-menu__item" @click="open = false">Open PDF</a>
            <a :href="printUrl" target="_blank" rel="noopener" class="admin-export-menu__item" @click="open = false">Print</a>
        </div>
    </div>
</template>
