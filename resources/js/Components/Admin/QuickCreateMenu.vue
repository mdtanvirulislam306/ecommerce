<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { Link } from '@inertiajs/vue3';
import { quickCreateItems } from '@/navigation/modules';
</script>

<template>
    <Dropdown align="right" width="48">
        <template #trigger>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-orange px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-orange-dark"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Create</span>
            </button>
        </template>
        <template #content>
            <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                Create New
            </div>
            <Link
                v-for="item in quickCreateItems"
                :key="item.path"
                :href="item.path"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            >
                {{ item.label }}
            </Link>
        </template>
    </Dropdown>
</template>
