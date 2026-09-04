<script setup>
import GlobalSearchModal from '@/Components/Admin/GlobalSearchModal.vue';
import QuickCreateMenu from '@/Components/Admin/QuickCreateMenu.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    searchOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['open-search', 'close-search']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <h1 v-if="title" class="min-w-0 truncate text-base font-semibold text-brand-navy sm:text-lg">
            {{ title }}
        </h1>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            <button
                type="button"
                class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-500 hover:border-gray-300 md:flex"
                @click="emit('open-search')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Search</span>
                <kbd class="rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] text-gray-400">⌘K</kbd>
            </button>

            <button
                type="button"
                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 md:hidden"
                @click="emit('open-search')"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            <QuickCreateMenu />

            <Dropdown align="right" width="48">
                <template #trigger>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-navy text-xs font-semibold text-white"
                    >
                        {{ user?.name?.charAt(0)?.toUpperCase() || '?' }}
                    </button>
                </template>
                <template #content>
                    <div class="px-4 py-2 text-sm text-gray-500">{{ user?.email }}</div>
                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">
                        Log Out
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>

        <GlobalSearchModal :show="searchOpen" @close="emit('close-search')" />
    </div>
</template>
