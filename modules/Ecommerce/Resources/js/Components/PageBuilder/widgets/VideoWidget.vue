<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const embedUrl = computed(() => {
    if (props.widget.data?.embed_url) {
        return props.widget.data.embed_url;
    }

    const url = (props.widget.settings?.url || '').trim();
    if (!url) {
        return null;
    }

    try {
        const parsed = new URL(url);
        const host = parsed.hostname.replace(/^www\./, '');

        if (host === 'youtu.be') {
            return `https://www.youtube-nocookie.com/embed/${parsed.pathname.replace(/^\//, '')}`;
        }
        if (host.includes('youtube.com')) {
            const id = parsed.searchParams.get('v') || parsed.pathname.split('/').filter(Boolean).pop();
            return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
        }
        if (host.includes('vimeo.com')) {
            const id = parsed.pathname.match(/(\d+)/)?.[1];
            return id ? `https://player.vimeo.com/video/${id}` : null;
        }
    } catch {
        return null;
    }

    return null;
});
</script>

<template>
    <div v-if="embedUrl" class="aspect-video overflow-hidden rounded-xl bg-black">
        <iframe
            :src="embedUrl"
            class="h-full w-full"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
            title="Video"
        />
    </div>
    <div
        v-else
        class="flex aspect-video items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-sm text-gray-400"
    >
        Paste a YouTube or Vimeo URL
    </div>
</template>
