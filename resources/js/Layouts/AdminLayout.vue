<script setup>
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import AdminTopbar from '@/Components/Admin/AdminTopbar.vue';
import { useGlobalSearch } from '@/composables/useGlobalSearch';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const impersonation = computed(() => page.props.impersonation);
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
            <div
                v-if="impersonation"
                class="sticky top-0 z-30 flex flex-wrap items-center gap-x-4 gap-y-2 bg-gradient-to-r from-amber-500 to-brand-orange px-4 py-2 text-sm text-white shadow-sm sm:px-6"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/20 ring-1 ring-white/30">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                <p class="min-w-0 flex-1">
                    <span class="font-semibold">Viewing as {{ page.props.auth.user?.name }}</span>
                    <span class="text-white/80"> · {{ page.props.tenant?.name }} · signed in by {{ impersonation.impersonator_name }}. Everything you do is recorded in the shop's audit log.</span>
                </p>
                <Link
                    :href="route('impersonation.leave')"
                    method="post"
                    as="button"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-brand-orange shadow-sm transition hover:bg-orange-50"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                    Return to platform
                </Link>
            </div>

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
