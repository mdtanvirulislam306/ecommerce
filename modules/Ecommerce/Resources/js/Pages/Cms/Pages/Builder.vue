<script setup>
import BuilderCanvas from '../../../Components/PageBuilder/BuilderCanvas.vue';
import BuilderInspector from '../../../Components/PageBuilder/BuilderInspector.vue';
import BuilderPalette from '../../../Components/PageBuilder/BuilderPalette.vue';
import PageBuilderLayout from '../../../Components/PageBuilder/PageBuilderLayout.vue';
import {
    applySectionLayout,
    cloneDocument,
    createBuilderId,
    findWidgetLocation,
    makeSection,
    makeWidget,
} from '../../../Composables/usePageBuilderDocument';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
    widgetCatalog: { type: Array, default: () => [] },
    layouts: { type: Array, default: () => [] },
    emptyDocument: { type: Object, default: () => ({ sections: [] }) },
    catalogPreview: { type: Object, default: () => ({ products: [], categories: [] }) },
});

const pageProps = usePage();
const flash = computed(() => pageProps.props.flash);
const isEditing = computed(() => Boolean(props.page?.id));
const device = ref('desktop');
const selection = ref(null);
const history = ref([]);
const future = ref([]);
const applyingHistory = ref(false);

const document = ref(cloneDocument(props.page?.blocks ?? props.emptyDocument ?? { sections: [] }));

const form = useForm({
    title: props.page?.title ?? 'Untitled page',
    slug: props.page?.slug ?? '',
    body: props.page?.body ?? '',
    blocks: document.value,
    is_published: props.page?.is_published ?? false,
    seo_title: props.page?.seo_title ?? '',
    seo_description: props.page?.seo_description ?? '',
});

const meta = computed(() => ({
    title: form.title,
    slug: form.slug,
    is_published: form.is_published,
    seo_title: form.seo_title,
    seo_description: form.seo_description,
}));

const selectedNode = computed(() => {
    if (!selection.value) {
        return null;
    }
    const section = document.value.sections.find((item) => item.id === selection.value.sectionId);
    if (!section) {
        return null;
    }
    if (selection.value.kind === 'section') {
        return section;
    }
    const column = section.columns.find((item) => item.id === selection.value.columnId);
    if (selection.value.kind === 'column') {
        return column ?? null;
    }
    return column?.widgets.find((item) => item.id === selection.value.widgetId) ?? null;
});

const snapshot = () => {
    if (applyingHistory.value) {
        return;
    }
    history.value.push(cloneDocument(document.value));
    if (history.value.length > 50) {
        history.value.shift();
    }
    future.value = [];
};

const undo = () => {
    if (!history.value.length) {
        return;
    }
    applyingHistory.value = true;
    future.value.push(cloneDocument(document.value));
    document.value = history.value.pop();
    applyingHistory.value = false;
};

const redo = () => {
    if (!future.value.length) {
        return;
    }
    applyingHistory.value = true;
    history.value.push(cloneDocument(document.value));
    document.value = future.value.pop();
    applyingHistory.value = false;
};

const onKeydown = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        if (event.shiftKey) {
            redo();
            return;
        }
        undo();
    }
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'y') {
        event.preventDefault();
        redo();
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

const setMeta = (next) => {
    form.title = next.title;
    form.slug = next.slug;
    form.is_published = next.is_published;
    form.seo_title = next.seo_title;
    form.seo_description = next.seo_description;
};

const updateSettings = ({ key, value }) => {
    if (!selection.value || !selectedNode.value) {
        return;
    }
    snapshot();
    selectedNode.value.settings[key] = value;
};

const updateRepeater = ({ key, index, field, value }) => {
    if (!selectedNode.value?.settings?.[key]) {
        return;
    }
    snapshot();
    selectedNode.value.settings[key][index][field] = value;
};

const addSectionAt = (layout, index, widgetType = null, defaults = {}) => {
    snapshot();
    const section = makeSection(props.layouts, layout);
    if (widgetType) {
        const catalogItem = props.widgetCatalog.find((item) => item.type === widgetType);
        section.columns[0].widgets.push(makeWidget(widgetType, defaults ?? catalogItem?.defaults ?? {}));
    }
    document.value.sections.splice(index, 0, section);
    selection.value = { kind: 'section', sectionId: section.id };
};

const dropWidget = ({ sectionId, columnId, type, defaults, index }) => {
    const section = document.value.sections.find((item) => item.id === sectionId);
    const column = section?.columns.find((item) => item.id === columnId);
    if (!column) {
        return;
    }
    snapshot();
    const catalogItem = props.widgetCatalog.find((item) => item.type === type);
    const widget = makeWidget(type, defaults ?? catalogItem?.defaults ?? {});
    if (index == null) {
        column.widgets.push(widget);
    } else {
        column.widgets.splice(index, 0, widget);
    }
    selection.value = { kind: 'widget', sectionId, columnId, widgetId: widget.id };
};

const dropSection = ({ layout, index, widgetType, defaults }) => {
    addSectionAt(layout, index, widgetType, defaults);
};

const moveWidget = ({ fromSectionId, fromColumnId, widgetId, toSectionId, toColumnId, index }) => {
    const fromSection = document.value.sections.find((item) => item.id === fromSectionId);
    const fromColumn = fromSection?.columns.find((item) => item.id === fromColumnId);
    const toSection = document.value.sections.find((item) => item.id === toSectionId);
    const toColumn = toSection?.columns.find((item) => item.id === toColumnId);
    if (!fromColumn || !toColumn) {
        return;
    }
    const currentIndex = fromColumn.widgets.findIndex((item) => item.id === widgetId);
    if (currentIndex === -1) {
        return;
    }
    snapshot();
    const [widget] = fromColumn.widgets.splice(currentIndex, 1);
    const targetIndex = index == null ? toColumn.widgets.length : index;
    if (fromColumn === toColumn && currentIndex < targetIndex) {
        toColumn.widgets.splice(targetIndex - 1, 0, widget);
    } else {
        toColumn.widgets.splice(targetIndex, 0, widget);
    }
    selection.value = { kind: 'widget', sectionId: toSectionId, columnId: toColumnId, widgetId };
};

const moveSection = ({ sectionId, index }) => {
    const currentIndex = document.value.sections.findIndex((item) => item.id === sectionId);
    if (currentIndex === -1) {
        return;
    }
    snapshot();
    const [section] = document.value.sections.splice(currentIndex, 1);
    const target = currentIndex < index ? index - 1 : index;
    document.value.sections.splice(Math.max(0, target), 0, section);
};

const removeSection = (sectionId) => {
    snapshot();
    document.value.sections = document.value.sections.filter((item) => item.id !== sectionId);
    selection.value = null;
};

const duplicateSection = (sectionId) => {
    const section = document.value.sections.find((item) => item.id === sectionId);
    if (!section) {
        return;
    }
    snapshot();
    const copy = cloneDocument(section);
    copy.id = createBuilderId();
    copy.columns = copy.columns.map((column) => ({
        ...column,
        id: createBuilderId(),
        widgets: column.widgets.map((widget) => ({ ...widget, id: createBuilderId() })),
    }));
    const index = document.value.sections.findIndex((item) => item.id === sectionId);
    document.value.sections.splice(index + 1, 0, copy);
};

const removeWidget = ({ sectionId, columnId, widgetId }) => {
    const section = document.value.sections.find((item) => item.id === sectionId);
    const column = section?.columns.find((item) => item.id === columnId);
    if (!column) {
        return;
    }
    snapshot();
    column.widgets = column.widgets.filter((item) => item.id !== widgetId);
    selection.value = { kind: 'column', sectionId, columnId };
};

const duplicateWidget = ({ sectionId, columnId, widgetId }) => {
    const found = findWidgetLocation(document.value, widgetId);
    if (!found) {
        return;
    }
    snapshot();
    const copy = { ...cloneDocument(found.widget), id: createBuilderId() };
    found.column.widgets.splice(found.index + 1, 0, copy);
};

const applyLayout = (layoutCode) => {
    const section = document.value.sections.find((item) => item.id === selection.value?.sectionId);
    if (!section) {
        return;
    }
    snapshot();
    applySectionLayout(section, props.layouts, layoutCode);
};

const save = () => {
    form.blocks = cloneDocument(document.value);
    if (isEditing.value) {
        form.put(route('ecommerce.pages.builder.update', props.page.id), { preserveScroll: true });
        return;
    }
    form.post(route('ecommerce.pages.builder.store'));
};

const previewUrl = computed(() => {
    const slug = form.slug || props.page?.slug;
    if (!slug) {
        return null;
    }
    return form.is_published || props.page?.is_published
        ? route('shop.pages.show', slug)
        : route('shop.pages.preview', slug);
});
</script>

<template>
    <Head :title="isEditing ? `Edit: ${form.title}` : 'Page Builder'" />

    <PageBuilderLayout>
        <template #header>
            <Link :href="route('ecommerce.pages.all.index')" class="text-sm text-brand-navy hover:text-brand-orange">
                ← Pages
            </Link>
            <input
                v-model="form.title"
                type="text"
                class="min-w-0 flex-1 rounded-md border-gray-200 text-sm font-medium"
            />
            <button type="button" class="text-sm text-brand-navy hover:text-brand-orange" @click="selection = null">
                Page
            </button>
            <div class="hidden items-center rounded-lg border border-gray-200 p-0.5 text-xs sm:flex">
                <button type="button" class="rounded-md px-2 py-1" :class="device === 'desktop' ? 'bg-gray-100 font-semibold' : ''" @click="device = 'desktop'">Desktop</button>
                <button type="button" class="rounded-md px-2 py-1" :class="device === 'tablet' ? 'bg-gray-100 font-semibold' : ''" @click="device = 'tablet'">Tablet</button>
                <button type="button" class="rounded-md px-2 py-1" :class="device === 'mobile' ? 'bg-gray-100 font-semibold' : ''" @click="device = 'mobile'">Mobile</button>
            </div>
            <button type="button" class="text-sm text-gray-500" :disabled="!history.length" @click="undo">Undo</button>
            <a
                v-if="previewUrl"
                :href="previewUrl"
                target="_blank"
                class="text-sm text-brand-navy hover:text-brand-orange"
            >
                Preview
            </a>
            <button
                type="button"
                class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-50"
                :disabled="form.processing"
                @click="save"
            >
                {{ form.processing ? 'Saving…' : (form.is_published ? 'Save & publish' : 'Save draft') }}
            </button>
        </template>

        <BuilderPalette :widget-catalog="widgetCatalog" :layouts="layouts" />

        <div class="flex min-w-0 flex-1 flex-col">
            <p v-if="flash?.success" class="border-b border-emerald-100 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
                {{ flash.success }}
            </p>
            <p v-if="Object.keys(form.errors).length" class="border-b border-red-100 bg-red-50 px-4 py-2 text-sm text-red-700">
                {{ Object.values(form.errors)[0] }}
            </p>
            <div class="min-h-0 flex-1 overflow-auto">
                <BuilderCanvas
                    :document="document"
                    :selection="selection"
                    :catalog-preview="catalogPreview"
                    :device="device"
                    @select="selection = $event"
                    @drop-widget="dropWidget"
                    @drop-section="dropSection"
                    @move-widget="moveWidget"
                    @move-section="moveSection"
                    @remove-section="removeSection"
                    @remove-widget="removeWidget"
                    @duplicate-widget="duplicateWidget"
                    @duplicate-section="duplicateSection"
                />
            </div>
        </div>

        <BuilderInspector
            :meta="meta"
            :selection="selection"
            :selected-node="selectedNode"
            :layouts="layouts"
            :errors="form.errors"
            @update:meta="setMeta"
            @update-settings="updateSettings"
            @update-repeater="updateRepeater"
            @apply-layout="applyLayout"
        />
    </PageBuilderLayout>
</template>
