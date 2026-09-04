import { onMounted, onUnmounted, ref } from 'vue';

export function useGlobalSearch() {
    const open = ref(false);

    const onKeydown = (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            open.value = true;
        }
        if (e.key === 'Escape' && open.value) {
            open.value = false;
        }
    };

    onMounted(() => document.addEventListener('keydown', onKeydown));
    onUnmounted(() => document.removeEventListener('keydown', onKeydown));

    return { open, close: () => (open.value = false) };
}
