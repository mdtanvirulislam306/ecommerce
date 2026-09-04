<script setup>
import Modal from '@/Components/Modal.vue';
import { searchCategories } from '@/navigation/modules';
import { ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const query = ref('');

watch(
    () => props.show,
    (visible) => {
        if (visible) query.value = '';
    },
);
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <div class="p-4">
            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="query"
                    type="search"
                    class="flex-1 border-0 bg-transparent text-sm text-brand-navy placeholder-gray-400 focus:ring-0"
                    placeholder="Search product, customer, order, invoice..."
                    autofocus
                />
                <kbd class="hidden rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] text-gray-400 sm:inline">ESC</kbd>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                Try: INV-2026-00125 — global search connects when data modules are live.
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <span
                    v-for="cat in searchCategories"
                    :key="cat"
                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600"
                >
                    {{ cat }}
                </span>
            </div>

            <div v-if="query" class="mt-6 text-center text-sm text-gray-500">
                No results yet — search backend coming with module data.
            </div>
        </div>
    </Modal>
</template>
