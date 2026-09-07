<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

defineProps({
    title: { type: String, default: 'POS Terminal' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isFullscreen = ref(false);

const syncFullscreen = () => {
    isFullscreen.value = Boolean(document.fullscreenElement);
};

const toggleFullscreen = async () => {
    try {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    } catch {
        // Browser may block fullscreen without a gesture or support.
    }
};

onMounted(() => {
    document.addEventListener('fullscreenchange', syncFullscreen);
    syncFullscreen();
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', syncFullscreen);
});
</script>

<template>
    <div class="flex h-dvh flex-col overflow-hidden bg-[#f3f5f9] text-slate-800">
        <header class="flex h-14 shrink-0 items-center justify-between gap-3 border-b border-slate-200/80 bg-white px-3 sm:h-16 sm:px-6">
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                <Link
                    :href="route('pos.registers.index')"
                    class="rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                >
                    ← POS
                </Link>
                <div class="hidden min-w-0 sm:block">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ title }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <slot name="header-actions" />
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-[#2563eb]/40 hover:text-[#2563eb]"
                    :title="isFullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    @click="toggleFullscreen"
                >
                    <svg v-if="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 9V5a1 1 0 011-1h4M20 9V5a1 1 0 00-1-1h-4M4 15v4a1 1 0 001 1h4M20 15v4a1 1 0 01-1 1h-4" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 4H5a1 1 0 00-1 1v4M15 4h4a1 1 0 011 1v4M9 20H5a1 1 0 01-1-1v-4M15 20h4a1 1 0 001-1v-4" />
                    </svg>
                    <span class="hidden sm:inline">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
                </button>
                <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 py-1 pl-1 pr-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#2563eb] text-xs font-bold text-white">
                        {{ (user?.name || 'A').charAt(0).toUpperCase() }}
                    </div>
                    <div class="hidden leading-tight sm:block">
                        <p class="text-xs font-semibold text-slate-900">{{ user?.name || 'Admin' }}</p>
                        <p class="text-[10px] text-slate-500">Cashier</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="min-h-0 flex-1 overflow-hidden p-2 sm:p-4">
            <slot />
        </main>
    </div>
</template>
