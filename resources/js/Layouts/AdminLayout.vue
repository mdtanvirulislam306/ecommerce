<script setup>
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import AdminTopbar from '@/Components/Admin/AdminTopbar.vue';
import { useGlobalSearch } from '@/composables/useGlobalSearch';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const mobileNavOpen = ref(false);
const { open: searchOpen, close: closeSearch } = useGlobalSearch();

watch(
    () => page.url,
    () => {
        mobileNavOpen.value = false;
    },
);
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Mobile overlay -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileNavOpen"
                class="fixed inset-0 z-40 bg-black/50 lg:hidden"
                @click="mobileNavOpen = false"
            />
        </Transition>

        <!-- Sidebar: drawer on mobile, static on desktop -->
        <div
            class="fixed inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0"
            :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <AdminSidebar @navigate="mobileNavOpen = false" />
        </div>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6">
                <button
                    type="button"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
                    aria-label="Open menu"
                    @click="mobileNavOpen = true"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <AdminTopbar
                    :title="title"
                    :search-open="searchOpen"
                    class="flex-1"
                    @open-search="searchOpen = true"
                    @close-search="closeSearch"
                />
            </header>

            <main class="flex-1 w-full p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
