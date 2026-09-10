<script setup>
import ButtonWidget from './ButtonWidget.vue';
import CategoriesWidget from './CategoriesWidget.vue';
import ContactFormWidget from './ContactFormWidget.vue';
import FaqWidget from './FaqWidget.vue';
import HeadingWidget from './HeadingWidget.vue';
import HeroWidget from './HeroWidget.vue';
import HtmlWidget from './HtmlWidget.vue';
import ImageWidget from './ImageWidget.vue';
import NewsletterWidget from './NewsletterWidget.vue';
import ProductGridWidget from './ProductGridWidget.vue';
import SpacerWidget from './SpacerWidget.vue';
import TestimonialsWidget from './TestimonialsWidget.vue';
import TextWidget from './TextWidget.vue';
import VideoWidget from './VideoWidget.vue';
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    pageSlug: { type: String, default: '' },
    catalogPreview: { type: Object, default: null },
});

const components = {
    heading: HeadingWidget,
    text: TextWidget,
    image: ImageWidget,
    button: ButtonWidget,
    spacer: SpacerWidget,
    video: VideoWidget,
    html: HtmlWidget,
    hero: HeroWidget,
    product_grid: ProductGridWidget,
    featured_products: ProductGridWidget,
    categories: CategoriesWidget,
    testimonials: TestimonialsWidget,
    faq: FaqWidget,
    newsletter: NewsletterWidget,
    contact_form: ContactFormWidget,
};

const resolved = computed(() => components[props.widget.type] ?? null);
</script>

<template>
    <component
        :is="resolved"
        v-if="resolved"
        :widget="widget"
        :editing="editing"
        :page-slug="pageSlug"
        :catalog-preview="catalogPreview"
    />
    <p v-else class="text-sm text-gray-400">Unknown widget: {{ widget.type }}</p>
</template>
