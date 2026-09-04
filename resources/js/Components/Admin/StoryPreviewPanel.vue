<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    previewUrl: { type: String, default: null },
    previewType: { type: String, default: 'image' },
    title: { type: String, default: '' },
    actionLabel: { type: String, default: '' },
});

const videoRef = ref(null);
const progress = ref(0);
const isPlaying = ref(false);

const IMAGE_STORY_MS = 5000;
let imageInterval = null;

const progressWidth = computed(() => `${Math.min(100, Math.max(0, progress.value))}%`);

const clearImageProgress = () => {
    if (imageInterval) {
        clearInterval(imageInterval);
        imageInterval = null;
    }
};

const resetVideo = () => {
    const video = videoRef.value;
    if (!video) {
        return;
    }

    video.pause();
    video.currentTime = 0;
    isPlaying.value = false;
};

const resetProgress = () => {
    progress.value = 0;
    clearImageProgress();
    resetVideo();
};

const startImageProgress = () => {
    clearImageProgress();
    progress.value = 0;

    const startedAt = Date.now();
    imageInterval = setInterval(() => {
        const elapsed = Date.now() - startedAt;
        progress.value = Math.min(100, (elapsed / IMAGE_STORY_MS) * 100);

        if (progress.value >= 100) {
            clearImageProgress();
        }
    }, 50);
};

const onVideoTimeUpdate = () => {
    const video = videoRef.value;
    if (!video?.duration) {
        return;
    }

    progress.value = (video.currentTime / video.duration) * 100;
};

const onVideoEnded = () => {
    isPlaying.value = false;
    progress.value = 100;
};

const toggleVideo = () => {
    const video = videoRef.value;
    if (!video) {
        return;
    }

    if (video.paused) {
        video.play();
        isPlaying.value = true;
    } else {
        video.pause();
        isPlaying.value = false;
    }
};

watch(
    () => [props.previewUrl, props.previewType],
    () => {
        resetProgress();

        if (props.previewUrl && props.previewType === 'image') {
            startImageProgress();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    clearImageProgress();
    resetVideo();
});
</script>

<template>
    <div class="w-full">
        <div class="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/10">
            <div class="relative aspect-[9/16] bg-gray-900">
                <template v-if="previewUrl">
                    <img
                        v-if="previewType === 'image'"
                        :src="previewUrl"
                        alt=""
                        class="absolute inset-0 h-full w-full object-cover"
                    />
                    <video
                        v-else
                        ref="videoRef"
                        :src="previewUrl"
                        class="absolute inset-0 h-full w-full cursor-pointer object-cover"
                        playsinline
                        @click="toggleVideo"
                        @timeupdate="onVideoTimeUpdate"
                        @ended="onVideoEnded"
                    />

                    <button
                        v-if="previewType === 'video' && !isPlaying"
                        type="button"
                        class="absolute inset-0 z-[5] flex items-center justify-center bg-black/20"
                        aria-label="Play video"
                        @click="toggleVideo"
                    >
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-black/50 text-white shadow-lg ring-1 ring-white/20">
                            <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </span>
                    </button>
                </template>
                <div
                    v-else
                    class="absolute inset-0 flex items-center justify-center bg-gray-800"
                >
                    <p class="px-4 text-center text-sm text-gray-500">
                        Upload media to preview
                    </p>
                </div>

                <div class="absolute inset-x-0 top-0 z-10 px-3 pt-3">
                    <div class="h-[3px] overflow-hidden rounded-full bg-white/30">
                        <div
                            class="h-full rounded-full bg-white"
                            :style="{ width: progressWidth }"
                        />
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[11px] text-white/80">
                        <span class="font-medium">Budget & Bazar</span>
                        <span class="text-white/50">✕</span>
                    </div>
                </div>

                <div
                    v-if="actionLabel"
                    class="absolute inset-x-0 bottom-0 z-10 px-3 pb-3"
                >
                    <span class="block rounded-lg bg-brand-orange py-2.5 text-center text-sm font-semibold text-white shadow-lg">
                        {{ actionLabel }}
                    </span>
                </div>
            </div>
        </div>

        <p class="mt-3 truncate text-center text-sm font-medium text-brand-navy">
            {{ title || 'Story title' }}
        </p>
    </div>
</template>
