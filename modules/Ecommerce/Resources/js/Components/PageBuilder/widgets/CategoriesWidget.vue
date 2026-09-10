<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    catalogPreview: { type: Object, default: null },
});

const categories = computed(() => {
    if (props.widget.data?.categories?.length) {
        return props.widget.data.categories;
    }
    const limit = Number(props.widget.settings?.limit) || 8;
    return (props.catalogPreview?.categories ?? []).slice(0, limit);
});
</script>

<template>
    <div class="space-y-4">
        <h2 v-if="widget.settings?.heading" class="text-2xl font-semibold text-brand-navy">
            {{ widget.settings.heading }}
        </h2>
        <div v-if="categories.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <component
                :is="editing ? 'div' : Link"
                v-for="category in categories"
                :key="category.id"
                :href="editing ? undefined : route('shop.index', { category: category.slug })"
                class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 transition hover:border-brand-orange"
            >
                <img
                    v-if="category.image_url"
                    :src="category.image_url"
                    alt=""
                    class="h-12 w-12 rounded-lg object-cover"
                />
                <span
                    v-else
                    class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-500"
                >
                    {{ category.name?.charAt(0) }}
                </span>
                <span class="min-w-0">
                    <span class="block truncate font-medium text-brand-navy">{{ category.name }}</span>
                    <span v-if="category.product_count != null" class="text-xs text-gray-400">
                        {{ category.product_count }} products
                    </span>
                </span>
            </component>
        </div>
        <p v-else class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">
            No categories yet.
        </p>
    </div>
</template>
