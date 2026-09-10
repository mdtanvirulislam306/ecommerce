<script setup>
import { writeDragPayload } from '../../Composables/usePageBuilderDocument';
import { computed } from 'vue';

const props = defineProps({
    widgetCatalog: { type: Array, default: () => [] },
    layouts: { type: Array, default: () => [] },
});

const groups = computed(() => {
    const order = ['content', 'shop', 'extra'];
    const labels = { content: 'Content', shop: 'Shop', extra: 'Extra' };

    return order
        .map((category) => ({
            category,
            label: labels[category],
            items: props.widgetCatalog.filter((item) => item.category === category),
        }))
        .filter((group) => group.items.length);
});

const onWidgetDrag = (event, item) => {
    writeDragPayload(event, { kind: 'palette-widget', type: item.type, defaults: item.defaults });
};

const onSectionDrag = (event, layout) => {
    writeDragPayload(event, { kind: 'palette-section', layout: layout.code });
};
</script>

<template>
    <aside class="flex w-64 shrink-0 flex-col overflow-y-auto border-r border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Sections</h2>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <button
                    v-for="layout in layouts"
                    :key="layout.code"
                    type="button"
                    draggable="true"
                    class="cursor-grab rounded-lg border border-gray-200 px-2 py-2 text-left text-[11px] font-medium text-brand-navy hover:border-brand-orange"
                    @dragstart="onSectionDrag($event, layout)"
                >
                    {{ layout.label }}
                </button>
            </div>
        </div>
        <div v-for="group in groups" :key="group.category" class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ group.label }}</h2>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <button
                    v-for="item in group.items"
                    :key="item.type"
                    type="button"
                    draggable="true"
                    class="cursor-grab rounded-lg border border-gray-200 px-2 py-2.5 text-left text-xs font-medium text-brand-navy hover:border-brand-orange"
                    @dragstart="onWidgetDrag($event, item)"
                >
                    {{ item.label }}
                </button>
            </div>
        </div>
    </aside>
</template>
