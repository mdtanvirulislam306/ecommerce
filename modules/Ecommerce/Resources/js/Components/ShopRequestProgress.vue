<script setup>
import { useShopUi } from '@/composables/useShopUi';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

const { state, beginRequest, endRequest } = useShopUi();

let removeStart;
let removeFinish;

onMounted(() => {
    removeStart = router.on('start', () => beginRequest());
    removeFinish = router.on('finish', () => endRequest());
});

onUnmounted(() => {
    removeStart?.();
    removeFinish?.();
});
</script>

<template>
    <div v-if="state.requesting || state.loadingProduct" class="shop-request-bar" aria-hidden="true">
        <div class="shop-request-bar__fill" />
    </div>
</template>
