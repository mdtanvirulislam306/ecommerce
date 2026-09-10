<script setup>
import WidgetRenderer from './widgets/WidgetRenderer.vue';
import { readDragPayload, writeDragPayload } from '../../Composables/usePageBuilderDocument';
import { ref } from 'vue';

const props = defineProps({
    document: { type: Object, required: true },
    selection: { type: Object, default: null },
    catalogPreview: { type: Object, default: null },
    device: { type: String, default: 'desktop' },
});

const emit = defineEmits([
    'select',
    'drop-widget',
    'drop-section',
    'move-widget',
    'move-section',
    'remove-section',
    'remove-widget',
    'duplicate-widget',
    'duplicate-section',
]);

const dropTarget = ref(null);
const sectionDropIndex = ref(null);

const deviceClass = {
    desktop: 'w-full',
    tablet: 'w-[768px] max-w-full',
    mobile: 'w-[390px] max-w-full',
};

const paddingClass = {
    none: 'py-0',
    sm: 'py-6',
    md: 'py-10',
    lg: 'py-16',
    xl: 'py-24',
};

const isSelected = (kind, ids) => {
    if (!props.selection || props.selection.kind !== kind) {
        return false;
    }
    return Object.entries(ids).every(([key, value]) => props.selection[key] === value);
};

const onDragOverColumn = (event, sectionId, columnId) => {
    event.preventDefault();
    dropTarget.value = `${sectionId}:${columnId}`;
};

const onDropColumn = (event, sectionId, columnId, index = null) => {
    event.preventDefault();
    dropTarget.value = null;
    const payload = readDragPayload(event);
    if (!payload) {
        return;
    }
    if (payload.kind === 'palette-widget') {
        emit('drop-widget', { sectionId, columnId, type: payload.type, defaults: payload.defaults, index });
        return;
    }
    if (payload.kind === 'widget') {
        emit('move-widget', {
            fromSectionId: payload.sectionId,
            fromColumnId: payload.columnId,
            widgetId: payload.widgetId,
            toSectionId: sectionId,
            toColumnId: columnId,
            index,
        });
    }
};

const onDragOverCanvas = (event, index) => {
    event.preventDefault();
    sectionDropIndex.value = index;
};

const onDropCanvas = (event, index) => {
    event.preventDefault();
    const payload = readDragPayload(event);
    sectionDropIndex.value = null;
    if (!payload) {
        return;
    }
    if (payload.kind === 'palette-section' || payload.kind === 'palette-widget') {
        emit('drop-section', { layout: payload.layout || '100', index, widgetType: payload.type, defaults: payload.defaults });
        return;
    }
    if (payload.kind === 'section') {
        emit('move-section', { sectionId: payload.sectionId, index });
    }
};

const onWidgetDragStart = (event, sectionId, columnId, widgetId) => {
    writeDragPayload(event, { kind: 'widget', sectionId, columnId, widgetId });
};

const onSectionDragStart = (event, sectionId) => {
    writeDragPayload(event, { kind: 'section', sectionId });
};
</script>

<template>
    <div
        class="min-h-full bg-slate-200/80 p-4"
        @click.self="emit('select', null)"
        @dragover.prevent="onDragOverCanvas($event, document.sections.length)"
        @drop="onDropCanvas($event, document.sections.length)"
        @dragleave="sectionDropIndex = null"
    >
        <div class="mx-auto min-h-[70vh] bg-white shadow-lg transition-[width]" :class="deviceClass[device]">
            <div
                v-if="document.sections.length === 0"
                class="flex min-h-[60vh] flex-col items-center justify-center gap-2 px-6 text-center text-sm text-gray-400"
            >
                <p class="font-medium text-gray-500">Drag a section or widget here</p>
                <p>The live shop page will look like this canvas.</p>
            </div>

            <template v-for="(section, sectionIndex) in document.sections" :key="section.id">
                <div
                    v-if="sectionDropIndex === sectionIndex"
                    class="h-2 bg-brand-orange/70"
                />
                <section
                    class="group/section relative"
                    :class="[
                        paddingClass[section.settings?.padding] || 'py-10',
                        isSelected('section', { sectionId: section.id }) ? 'ring-2 ring-brand-orange' : '',
                    ]"
                    :style="{
                        backgroundColor: section.settings?.background || '#ffffff',
                        backgroundImage: section.settings?.background_image ? `url(${section.settings.background_image})` : undefined,
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                    }"
                    draggable="true"
                    @click.stop="emit('select', { kind: 'section', sectionId: section.id })"
                    @dragstart="onSectionDragStart($event, section.id)"
                    @dragover.prevent="onDragOverCanvas($event, sectionIndex)"
                    @drop.stop="onDropCanvas($event, sectionIndex)"
                >
                    <div class="absolute left-2 top-2 z-10 hidden gap-1 group-hover/section:flex">
                        <button type="button" class="rounded bg-brand-navy px-2 py-0.5 text-[10px] font-semibold text-white" @click.stop="emit('duplicate-section', section.id)">Dup</button>
                        <button type="button" class="rounded bg-red-600 px-2 py-0.5 text-[10px] font-semibold text-white" @click.stop="emit('remove-section', section.id)">Del</button>
                    </div>
                    <div
                        class="flex gap-4 px-4 sm:px-8"
                        :class="section.settings?.full_width ? 'w-full' : 'mx-auto max-w-6xl'"
                    >
                        <div
                            v-for="column in section.columns"
                            :key="column.id"
                            class="min-h-[5rem] rounded-lg border border-dashed p-2 transition"
                            :class="dropTarget === `${section.id}:${column.id}` || isSelected('column', { sectionId: section.id, columnId: column.id })
                                ? 'border-brand-orange bg-brand-orange/5'
                                : 'border-transparent group-hover/section:border-gray-200'"
                            :style="{ flex: `0 0 ${column.width}%`, maxWidth: `${column.width}%` }"
                            @click.stop="emit('select', { kind: 'column', sectionId: section.id, columnId: column.id })"
                            @dragover.prevent="onDragOverColumn($event, section.id, column.id)"
                            @drop.stop="onDropColumn($event, section.id, column.id)"
                            @dragleave="dropTarget = null"
                        >
                            <div
                                v-for="widget in column.widgets"
                                :key="widget.id"
                                class="group/widget relative mb-3 last:mb-0"
                                :class="isSelected('widget', { widgetId: widget.id }) ? 'ring-2 ring-brand-orange ring-offset-2' : 'hover:ring-1 hover:ring-brand-orange/40'"
                                draggable="true"
                                @click.stop="emit('select', { kind: 'widget', sectionId: section.id, columnId: column.id, widgetId: widget.id })"
                                @dragstart.stop="onWidgetDragStart($event, section.id, column.id, widget.id)"
                            >
                                <div class="absolute right-1 top-1 z-10 hidden gap-1 group-hover/widget:flex">
                                    <button type="button" class="rounded bg-brand-navy px-1.5 py-0.5 text-[10px] text-white" @click.stop="emit('duplicate-widget', { sectionId: section.id, columnId: column.id, widgetId: widget.id })">Dup</button>
                                    <button type="button" class="rounded bg-red-600 px-1.5 py-0.5 text-[10px] text-white" @click.stop="emit('remove-widget', { sectionId: section.id, columnId: column.id, widgetId: widget.id })">Del</button>
                                </div>
                                <div class="pointer-events-none p-1">
                                    <WidgetRenderer
                                        :widget="widget"
                                        editing
                                        :catalog-preview="catalogPreview"
                                    />
                                </div>
                            </div>
                            <p v-if="!column.widgets.length" class="px-2 py-6 text-center text-[11px] text-gray-400">
                                Drop widgets here
                            </p>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </div>
</template>
