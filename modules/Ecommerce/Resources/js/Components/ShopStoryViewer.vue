<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    stories: { type: Array, default: () => [] },
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const IMAGE_DURATION_MS = 5500;

const index = ref(0);
const progress = ref(0);
const paused = ref(false);
const videoRef = ref(null);

let rafId = null;
let startedAt = 0;
let elapsedBeforePause = 0;
let imageDuration = IMAGE_DURATION_MS;

const current = computed(() => props.stories[index.value] ?? null);
const isVideo = computed(() => current.value?.type === 'video');
const shopHref = computed(() => current.value?.action_url || route('shop.index'));
const shopLabel = computed(() => current.value?.action_label || 'Order Now');

const clearTicker = () => {
    if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
};

const stopVideo = () => {
    const el = videoRef.value;
    if (!el) {
        return;
    }
    el.pause();
    el.removeAttribute('src');
    el.load();
};

const goNext = () => {
    if (index.value >= props.stories.length - 1) {
        close();
        return;
    }
    index.value += 1;
};

const goPrev = () => {
    if (index.value <= 0) {
        restartCurrent();
        return;
    }
    index.value -= 1;
};

const tickImage = (now) => {
    if (paused.value || !props.show || isVideo.value) {
        return;
    }
    const elapsed = elapsedBeforePause + (now - startedAt);
    progress.value = Math.min(1, elapsed / imageDuration);
    if (progress.value >= 1) {
        goNext();
        return;
    }
    rafId = requestAnimationFrame(tickImage);
};

const startImageTimer = () => {
    clearTicker();
    progress.value = 0;
    elapsedBeforePause = 0;
    imageDuration = IMAGE_DURATION_MS;
    startedAt = performance.now();
    paused.value = false;
    rafId = requestAnimationFrame(tickImage);
};

const onVideoTimeUpdate = () => {
    const el = videoRef.value;
    if (!el || !el.duration || Number.isNaN(el.duration)) {
        return;
    }
    progress.value = Math.min(1, el.currentTime / el.duration);
};

const onVideoEnded = () => {
    goNext();
};

const playVideo = async () => {
    clearTicker();
    progress.value = 0;
    await nextTick();
    const el = videoRef.value;
    if (!el) {
        return;
    }
    try {
        el.currentTime = 0;
        el.muted = true;
        await el.play();
    } catch {
        // Autoplay may be blocked; still show the first frame.
    }
};

const restartCurrent = async () => {
    clearTicker();
    progress.value = 0;
    elapsedBeforePause = 0;
    paused.value = false;

    if (isVideo.value) {
        await playVideo();
        return;
    }

    startImageTimer();
};

const pause = () => {
    if (paused.value) {
        return;
    }
    paused.value = true;
    if (isVideo.value) {
        videoRef.value?.pause();
        return;
    }
    elapsedBeforePause += performance.now() - startedAt;
    clearTicker();
};

const resume = () => {
    if (!paused.value) {
        return;
    }
    paused.value = false;
    if (isVideo.value) {
        videoRef.value?.play()?.catch(() => {});
        return;
    }
    startedAt = performance.now();
    rafId = requestAnimationFrame(tickImage);
};

const close = () => {
    clearTicker();
    stopVideo();
    emit('close');
};

const onKeydown = (event) => {
    if (!props.show) {
        return;
    }
    if (event.key === 'Escape') {
        close();
    } else if (event.key === 'ArrowRight') {
        goNext();
    } else if (event.key === 'ArrowLeft') {
        goPrev();
    }
};

watch(
    () => [props.show, props.stories],
    async ([show]) => {
        if (!show) {
            clearTicker();
            stopVideo();
            return;
        }
        index.value = 0;
        await restartCurrent();
    },
    { immediate: true, deep: true },
);

watch(index, async () => {
    if (!props.show) {
        return;
    }
    await restartCurrent();
});

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    clearTicker();
    stopVideo();
    window.removeEventListener('keydown', onKeydown);
});

const segmentFill = (i) => {
    if (i < index.value) {
        return '100%';
    }
    if (i > index.value) {
        return '0%';
    }
    return `${progress.value * 100}%`;
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show && current"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-brand-navy/80 p-0 sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Story viewer"
            @click.self="close"
        >
            <div
                class="relative flex h-[100dvh] w-full overflow-hidden bg-black shadow-2xl shadow-black/40 sm:h-[min(92vh,760px)] sm:max-w-[420px] sm:rounded-3xl"
                @pointerdown="pause"
                @pointerup="resume"
                @pointerleave="resume"
                @pointercancel="resume"
            >
                <video
                    v-if="isVideo"
                    :key="`video-${current.id}`"
                    ref="videoRef"
                    class="absolute inset-0 h-full w-full object-cover"
                    :src="current.media_url"
                    playsinline
                    muted
                    autoplay
                    preload="auto"
                    @timeupdate="onVideoTimeUpdate"
                    @ended="onVideoEnded"
                />
                <img
                    v-else
                    :key="`image-${current.id}`"
                    :src="current.media_url"
                    :alt="current.title"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/45 via-transparent to-black/35" />

                <div class="absolute inset-x-0 top-0 z-20 px-3 pt-3">
                    <div class="flex gap-1">
                        <div
                            v-for="(story, i) in stories"
                            :key="`seg-${story.id}`"
                            class="h-1 flex-1 overflow-hidden rounded-full bg-white/35"
                        >
                            <div
                                class="h-full rounded-full bg-white transition-[width] duration-75 ease-linear"
                                :style="{ width: segmentFill(i) }"
                            />
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-black/35 text-white transition hover:bg-black/50"
                            aria-label="Close story"
                            @click.stop="close"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="absolute inset-y-0 left-0 z-10 w-1/3 cursor-w-resize bg-transparent"
                    aria-label="Previous story"
                    @click.stop="goPrev"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 z-10 w-1/3 cursor-e-resize bg-transparent"
                    aria-label="Next story"
                    @click.stop="goNext"
                />

                <button
                    v-if="index > 0"
                    type="button"
                    class="absolute left-2 top-1/2 z-30 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-navy shadow sm:flex"
                    aria-label="Previous"
                    @click.stop="goPrev"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    v-if="index < stories.length - 1"
                    type="button"
                    class="absolute right-2 top-1/2 z-30 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-navy shadow sm:flex"
                    aria-label="Next"
                    @click.stop="goNext"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div class="absolute inset-x-0 bottom-0 z-20 p-4 pb-5 sm:p-5 sm:pb-6">
                    <a
                        :href="shopHref"
                        class="flex w-full items-center justify-center rounded-xl bg-white px-4 py-3.5 text-sm font-semibold text-brand-navy shadow-lg transition hover:bg-brand-orange hover:text-white"
                        @click.stop
                    >
                        {{ shopLabel }}
                    </a>
                </div>
            </div>
        </div>
    </Teleport>
</template>
