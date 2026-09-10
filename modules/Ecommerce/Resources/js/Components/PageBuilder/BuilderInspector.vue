<script setup>
import MediaPicker from '@/Components/Admin/MediaPicker.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    meta: { type: Object, required: true },
    selection: { type: Object, default: null },
    selectedNode: { type: Object, default: null },
    layouts: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:meta', 'update-settings', 'apply-layout', 'update-repeater']);

const showPicker = ref(false);
const pickerTarget = ref('image');

const selectedWidget = computed(() => (
    props.selection?.kind === 'widget' ? props.selectedNode : null
));

const selectedSection = computed(() => (
    props.selection?.kind === 'section' ? props.selectedNode : null
));

const setMeta = (key, value) => {
    emit('update:meta', { ...props.meta, [key]: value });
};

const setSetting = (key, value) => {
    emit('update-settings', { key, value });
};

const setNestedSetting = (key, index, field, value) => {
    emit('update-repeater', { key, index, field, value });
};

const addRepeaterItem = (key, item) => {
    const current = [...(selectedWidget.value?.settings?.[key] ?? [])];
    current.push(item);
    setSetting(key, current);
};

const removeRepeaterItem = (key, index) => {
    const current = [...(selectedWidget.value?.settings?.[key] ?? [])];
    current.splice(index, 1);
    setSetting(key, current);
};

const openPicker = (target) => {
    pickerTarget.value = target;
    showPicker.value = true;
};

const onMedia = (item) => {
    if (!item) {
        return;
    }
    if (pickerTarget.value === 'image') {
        setSetting('url', item.url);
        setSetting('media_id', item.id);
        return;
    }
    if (pickerTarget.value === 'hero') {
        setSetting('image_url', item.url);
        setSetting('media_id', item.id);
        return;
    }
    if (pickerTarget.value === 'section-bg') {
        setSetting('background_image', item.url);
        return;
    }
    if (pickerTarget.value.startsWith('avatar:')) {
        const index = Number(pickerTarget.value.split(':')[1]);
        setNestedSetting('items', index, 'avatar_url', item.url);
    }
};

const fieldClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm';
</script>

<template>
    <aside class="flex w-80 shrink-0 flex-col overflow-y-auto border-l border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-brand-navy">
                {{ selectedWidget ? 'Widget' : selectedSection ? 'Section' : selection?.kind === 'column' ? 'Column' : 'Page' }}
            </h2>
        </div>

        <div class="space-y-4 px-4 py-4">
            <template v-if="!selection">
                <div>
                    <InputLabel value="Title" />
                    <input :value="meta.title" type="text" :class="fieldClass" @input="setMeta('title', $event.target.value)" />
                    <p v-if="errors.title" class="mt-1 text-xs text-red-600">{{ errors.title }}</p>
                </div>
                <div>
                    <InputLabel value="Slug" />
                    <input :value="meta.slug" type="text" :class="fieldClass" @input="setMeta('slug', $event.target.value)" />
                    <p v-if="errors.slug" class="mt-1 text-xs text-red-600">{{ errors.slug }}</p>
                </div>
                <div>
                    <InputLabel value="SEO title" />
                    <input :value="meta.seo_title" type="text" :class="fieldClass" @input="setMeta('seo_title', $event.target.value)" />
                </div>
                <div>
                    <InputLabel value="SEO description" />
                    <textarea :value="meta.seo_description" rows="3" :class="fieldClass" @input="setMeta('seo_description', $event.target.value)" />
                </div>
                <label class="flex items-center gap-2">
                    <Checkbox :checked="Boolean(meta.is_published)" @update:checked="setMeta('is_published', $event)" />
                    <span class="text-sm">Published</span>
                </label>
            </template>

            <template v-else-if="selectedSection">
                <div>
                    <InputLabel value="Layout" />
                    <select
                        :value="selectedSection.settings.layout"
                        :class="fieldClass"
                        @change="emit('apply-layout', $event.target.value)"
                    >
                        <option v-for="layout in layouts" :key="layout.code" :value="layout.code">{{ layout.label }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Padding" />
                    <select :value="selectedSection.settings.padding" :class="fieldClass" @change="setSetting('padding', $event.target.value)">
                        <option value="none">None</option>
                        <option value="sm">Small</option>
                        <option value="md">Medium</option>
                        <option value="lg">Large</option>
                        <option value="xl">Extra large</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Background color" />
                    <input :value="selectedSection.settings.background" type="color" class="mt-1 h-9 w-full rounded border border-gray-300" @input="setSetting('background', $event.target.value)" />
                </div>
                <div>
                    <InputLabel value="Background image" />
                    <button type="button" class="mt-1 text-sm text-brand-orange" @click="openPicker('section-bg')">Choose image</button>
                    <button v-if="selectedSection.settings.background_image" type="button" class="ml-3 text-sm text-gray-500" @click="setSetting('background_image', '')">Clear</button>
                </div>
                <label class="flex items-center gap-2">
                    <Checkbox :checked="Boolean(selectedSection.settings.full_width)" @update:checked="setSetting('full_width', $event)" />
                    <span class="text-sm">Full width</span>
                </label>
            </template>

            <p v-else-if="selection?.kind === 'column'" class="text-sm text-gray-500">
                Drop widgets into this column. Choose a section to change the column layout.
            </p>

            <template v-else-if="selectedWidget">
                <template v-if="selectedWidget.type === 'heading'">
                    <div>
                        <InputLabel value="Text" />
                        <input :value="selectedWidget.settings.text" type="text" :class="fieldClass" @input="setSetting('text', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Tag" />
                        <select :value="selectedWidget.settings.tag" :class="fieldClass" @change="setSetting('tag', $event.target.value)">
                            <option value="h1">H1</option>
                            <option value="h2">H2</option>
                            <option value="h3">H3</option>
                            <option value="h4">H4</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Align" />
                        <select :value="selectedWidget.settings.align" :class="fieldClass" @change="setSetting('align', $event.target.value)">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Color" />
                        <input :value="selectedWidget.settings.color" type="color" class="mt-1 h-9 w-full rounded border border-gray-300" @input="setSetting('color', $event.target.value)" />
                    </div>
                </template>

                <template v-else-if="selectedWidget.type === 'text'">
                    <InputLabel value="Content" />
                    <textarea :value="selectedWidget.settings.content" rows="8" :class="fieldClass" @input="setSetting('content', $event.target.value)" />
                    <div class="mt-3">
                        <InputLabel value="Align" />
                        <select :value="selectedWidget.settings.align" :class="fieldClass" @change="setSetting('align', $event.target.value)">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                </template>

                <template v-else-if="selectedWidget.type === 'image'">
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-2 text-sm" @click="openPicker('image')">Choose image</button>
                    <div class="mt-3">
                        <InputLabel value="Alt text" />
                        <input :value="selectedWidget.settings.alt" type="text" :class="fieldClass" @input="setSetting('alt', $event.target.value)" />
                    </div>
                    <div class="mt-3">
                        <InputLabel value="Width" />
                        <select :value="selectedWidget.settings.width" :class="fieldClass" @change="setSetting('width', $event.target.value)">
                            <option value="full">Full</option>
                            <option value="contained">Contained</option>
                        </select>
                    </div>
                </template>

                <template v-else-if="selectedWidget.type === 'button'">
                    <div>
                        <InputLabel value="Label" />
                        <input :value="selectedWidget.settings.label" type="text" :class="fieldClass" @input="setSetting('label', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Link" />
                        <input :value="selectedWidget.settings.url" type="text" :class="fieldClass" @input="setSetting('url', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Style" />
                        <select :value="selectedWidget.settings.style" :class="fieldClass" @change="setSetting('style', $event.target.value)">
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Align" />
                        <select :value="selectedWidget.settings.align" :class="fieldClass" @change="setSetting('align', $event.target.value)">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                </template>

                <template v-else-if="selectedWidget.type === 'spacer'">
                    <InputLabel value="Height (px)" />
                    <input :value="selectedWidget.settings.height" type="number" min="8" max="240" :class="fieldClass" @input="setSetting('height', Number($event.target.value))" />
                </template>

                <template v-else-if="selectedWidget.type === 'video'">
                    <InputLabel value="YouTube or Vimeo URL" />
                    <input :value="selectedWidget.settings.url" type="url" :class="fieldClass" @input="setSetting('url', $event.target.value)" />
                    <p v-if="errors['blocks.sections']" class="mt-1 text-xs text-red-600">{{ errors['blocks.sections'] }}</p>
                </template>

                <template v-else-if="selectedWidget.type === 'html'">
                    <InputLabel value="HTML" />
                    <textarea :value="selectedWidget.settings.content" rows="10" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs" @input="setSetting('content', $event.target.value)" />
                </template>

                <template v-else-if="selectedWidget.type === 'hero'">
                    <div>
                        <InputLabel value="Title" />
                        <input :value="selectedWidget.settings.title" type="text" :class="fieldClass" @input="setSetting('title', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Subtitle" />
                        <textarea :value="selectedWidget.settings.subtitle" rows="3" :class="fieldClass" @input="setSetting('subtitle', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Button label" />
                        <input :value="selectedWidget.settings.cta_label" type="text" :class="fieldClass" @input="setSetting('cta_label', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Button link" />
                        <input :value="selectedWidget.settings.cta_url" type="text" :class="fieldClass" @input="setSetting('cta_url', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Overlay" />
                        <input :value="selectedWidget.settings.overlay" type="range" min="0" max="80" class="mt-1 w-full" @input="setSetting('overlay', Number($event.target.value))" />
                    </div>
                    <button type="button" class="text-sm text-brand-orange" @click="openPicker('hero')">Background image</button>
                </template>

                <template v-else-if="selectedWidget.type === 'product_grid' || selectedWidget.type === 'featured_products'">
                    <div>
                        <InputLabel value="Heading" />
                        <input :value="selectedWidget.settings.heading" type="text" :class="fieldClass" @input="setSetting('heading', $event.target.value)" />
                    </div>
                    <div v-if="selectedWidget.type === 'product_grid'">
                        <InputLabel value="Source" />
                        <select :value="selectedWidget.settings.source" :class="fieldClass" @change="setSetting('source', $event.target.value)">
                            <option value="latest">Latest</option>
                            <option value="featured">Featured</option>
                            <option value="homepage">Homepage</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Limit" />
                        <input :value="selectedWidget.settings.limit" type="number" min="1" max="24" :class="fieldClass" @input="setSetting('limit', Number($event.target.value))" />
                    </div>
                    <div>
                        <InputLabel value="Columns" />
                        <select :value="String(selectedWidget.settings.columns)" :class="fieldClass" @change="setSetting('columns', Number($event.target.value))">
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="6">6</option>
                        </select>
                    </div>
                </template>

                <template v-else-if="selectedWidget.type === 'categories'">
                    <div>
                        <InputLabel value="Heading" />
                        <input :value="selectedWidget.settings.heading" type="text" :class="fieldClass" @input="setSetting('heading', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Limit" />
                        <input :value="selectedWidget.settings.limit" type="number" min="1" max="24" :class="fieldClass" @input="setSetting('limit', Number($event.target.value))" />
                    </div>
                </template>

                <template v-else-if="selectedWidget.type === 'testimonials'">
                    <div>
                        <InputLabel value="Heading" />
                        <input :value="selectedWidget.settings.heading" type="text" :class="fieldClass" @input="setSetting('heading', $event.target.value)" />
                    </div>
                    <div v-for="(item, index) in selectedWidget.settings.items" :key="index" class="rounded-lg border border-gray-200 p-3">
                        <textarea :value="item.quote" rows="2" :class="fieldClass" placeholder="Quote" @input="setNestedSetting('items', index, 'quote', $event.target.value)" />
                        <input :value="item.name" type="text" :class="fieldClass" placeholder="Name" @input="setNestedSetting('items', index, 'name', $event.target.value)" />
                        <input :value="item.role" type="text" :class="fieldClass" placeholder="Role" @input="setNestedSetting('items', index, 'role', $event.target.value)" />
                        <button type="button" class="mt-1 text-xs text-brand-orange" @click="openPicker(`avatar:${index}`)">Avatar</button>
                        <button type="button" class="ml-2 mt-1 text-xs text-red-600" @click="removeRepeaterItem('items', index)">Remove</button>
                    </div>
                    <button type="button" class="text-sm text-brand-orange" @click="addRepeaterItem('items', { quote: '', name: '', role: '', avatar_url: '' })">Add testimonial</button>
                </template>

                <template v-else-if="selectedWidget.type === 'faq'">
                    <div>
                        <InputLabel value="Heading" />
                        <input :value="selectedWidget.settings.heading" type="text" :class="fieldClass" @input="setSetting('heading', $event.target.value)" />
                    </div>
                    <div v-for="(item, index) in selectedWidget.settings.items" :key="index" class="rounded-lg border border-gray-200 p-3">
                        <input :value="item.question" type="text" :class="fieldClass" placeholder="Question" @input="setNestedSetting('items', index, 'question', $event.target.value)" />
                        <textarea :value="item.answer" rows="3" :class="fieldClass" placeholder="Answer" @input="setNestedSetting('items', index, 'answer', $event.target.value)" />
                        <button type="button" class="mt-1 text-xs text-red-600" @click="removeRepeaterItem('items', index)">Remove</button>
                    </div>
                    <button type="button" class="text-sm text-brand-orange" @click="addRepeaterItem('items', { question: '', answer: '' })">Add question</button>
                </template>

                <template v-else-if="selectedWidget.type === 'newsletter' || selectedWidget.type === 'contact_form'">
                    <div>
                        <InputLabel value="Heading" />
                        <input :value="selectedWidget.settings.heading" type="text" :class="fieldClass" @input="setSetting('heading', $event.target.value)" />
                    </div>
                    <div v-if="selectedWidget.type === 'newsletter'">
                        <InputLabel value="Placeholder" />
                        <input :value="selectedWidget.settings.placeholder" type="text" :class="fieldClass" @input="setSetting('placeholder', $event.target.value)" />
                    </div>
                    <div>
                        <InputLabel value="Button label" />
                        <input :value="selectedWidget.settings.button_label" type="text" :class="fieldClass" @input="setSetting('button_label', $event.target.value)" />
                    </div>
                </template>
            </template>
        </div>

        <MediaPicker :show="showPicker" :multiple="false" @close="showPicker = false" @select="onMedia" />
    </aside>
</template>
