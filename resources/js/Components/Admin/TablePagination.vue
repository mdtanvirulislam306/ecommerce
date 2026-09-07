<script setup>
import { paginationMeta } from '@/utils/paginationMeta';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    paginator: {
        type: Object,
        default: () => ({}),
    },
});

const meta = computed(() => paginationMeta(props.paginator));
const resolvedLinks = computed(() =>
    props.links.length > 0 ? props.links : (props.paginator?.links ?? []),
);
</script>

<template>
    <nav v-if="meta.last_page > 1" class="flex items-center gap-1">
        <template v-for="(link, index) in resolvedLinks" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="inline-flex min-w-8 items-center justify-center rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors"
                :class="
                    link.active
                        ? 'bg-brand-navy text-white'
                        : 'text-gray-600 hover:bg-gray-100'
                "
                preserve-scroll
                preserve-state
            >
                <span v-html="link.label" />
            </Link>
            <span
                v-else
                class="inline-flex min-w-8 items-center justify-center px-2.5 py-1.5 text-xs text-gray-400"
            >
                <span v-html="link.label" />
            </span>
        </template>
    </nav>
</template>
