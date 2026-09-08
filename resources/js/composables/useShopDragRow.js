import { reactive, ref } from 'vue';

const DRAG_THRESHOLD_PX = 5;

export function useShopDragRow() {
    const row = ref(null);
    const drag = reactive({
        pointerId: null,
        startX: 0,
        startScroll: 0,
        moved: false,
    });

    const onPointerDown = (event) => {
        if (event.pointerType === 'touch') {
            return;
        }

        const el = row.value;
        if (!el) {
            return;
        }

        drag.pointerId = event.pointerId;
        drag.startX = event.clientX;
        drag.startScroll = el.scrollLeft;
        drag.moved = false;
    };

    const onPointerMove = (event) => {
        if (drag.pointerId !== event.pointerId) {
            return;
        }

        const el = row.value;
        if (!el) {
            return;
        }

        const dx = event.clientX - drag.startX;
        if (!drag.moved) {
            if (Math.abs(dx) < DRAG_THRESHOLD_PX) {
                return;
            }

            drag.moved = true;
            el.setPointerCapture(event.pointerId);
        }

        el.scrollLeft = drag.startScroll - dx;
    };

    const endDrag = (event) => {
        if (drag.pointerId !== event.pointerId) {
            return;
        }

        const el = row.value;
        if (el?.hasPointerCapture?.(event.pointerId)) {
            el.releasePointerCapture(event.pointerId);
        }
        drag.pointerId = null;
    };

    const onClickCapture = (event) => {
        if (!drag.moved) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        drag.moved = false;
    };

    return {
        row,
        drag,
        onPointerDown,
        onPointerMove,
        endDrag,
        onClickCapture,
    };
}
