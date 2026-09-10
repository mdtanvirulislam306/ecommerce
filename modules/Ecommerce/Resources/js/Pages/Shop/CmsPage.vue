<script setup>
import WidgetRenderer from '../../Components/PageBuilder/widgets/WidgetRenderer.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    page: { type: Object, required: true },
});

const sections = computed(() => props.page.blocks?.sections ?? []);

const paddingClass = {
    none: 'py-0',
    sm: 'py-6',
    md: 'py-10',
    lg: 'py-16',
    xl: 'py-24',
};
</script>

<template>
    <Head :title="page.seo_title || page.title">
        <meta v-if="page.seo_description" head-key="description" name="description" :content="page.seo_description" />
    </Head>

    <StorefrontLayout>
        <article>
            <section
                v-for="section in sections"
                :key="section.id"
                :class="paddingClass[section.settings?.padding] || 'py-10'"
                :style="{
                    backgroundColor: section.settings?.background || '#ffffff',
                    backgroundImage: section.settings?.background_image ? `url(${section.settings.background_image})` : undefined,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                }"
            >
                <div
                    class="flex flex-col gap-6 px-4 sm:flex-row sm:gap-4 sm:px-8"
                    :class="section.settings?.full_width ? 'w-full' : 'mx-auto max-w-6xl'"
                >
                    <div
                        v-for="column in section.columns"
                        :key="column.id"
                        class="min-w-0 space-y-4"
                        :style="{ flex: `1 1 ${column.width}%`, maxWidth: '100%' }"
                    >
                        <WidgetRenderer
                            v-for="widget in column.widgets"
                            :key="widget.id"
                            :widget="widget"
                            :page-slug="page.slug"
                        />
                    </div>
                </div>
            </section>
            <p
                v-if="!sections.length"
                class="px-4 py-24 text-center text-sm text-gray-400"
            >
                This page has no content yet.
            </p>
        </article>
    </StorefrontLayout>
</template>
