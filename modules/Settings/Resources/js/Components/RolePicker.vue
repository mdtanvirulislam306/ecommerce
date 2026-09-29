<script setup>
import { Link } from '@inertiajs/vue3';

const selected = defineModel({ type: Array, default: () => [] });

defineProps({
    roles: { type: Array, required: true },
});

const toggle = (id) => {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((value) => value !== id)
        : [...selected.value, id];
};
</script>

<template>
    <div>
        <div v-if="!roles.length" class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center">
            <p class="text-sm text-gray-600">You haven't created any roles yet.</p>
            <Link :href="route('settings.roles.index')" class="mt-2 inline-block text-sm font-semibold text-brand-teal-dark hover:underline">
                Create a role first →
            </Link>
        </div>
        <div v-else class="grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
            <button
                v-for="role in roles"
                :key="role.id"
                type="button"
                class="flex items-start gap-3 rounded-xl border p-3 text-left transition"
                :class="selected.includes(role.id)
                    ? 'border-brand-teal bg-brand-teal/5 ring-2 ring-brand-teal/20'
                    : 'border-gray-200 hover:border-gray-300'"
                :aria-pressed="selected.includes(role.id)"
                @click="toggle(role.id)"
            >
                <span
                    class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 transition"
                    :class="selected.includes(role.id) ? 'border-brand-teal bg-brand-teal text-white' : 'border-gray-300'"
                >
                    <svg v-if="selected.includes(role.id)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-brand-navy">{{ role.name }}</span>
                    <span class="block text-xs text-gray-500">
                        {{ role.description || `${role.permissions_count} permission${role.permissions_count === 1 ? '' : 's'}` }}
                    </span>
                </span>
            </button>
        </div>
    </div>
</template>
