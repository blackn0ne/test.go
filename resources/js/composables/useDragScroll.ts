import { onMounted, onUnmounted, ref, type Ref } from 'vue';

type DragScrollOptions = {
    dragThreshold?: number;
};

export function useDragScroll(
    containerRef: Ref<HTMLElement | null>,
    options: DragScrollOptions = {},
) {
    const dragThreshold = options.dragThreshold ?? 4;
    const isDragging = ref(false);
    const suppressNextClick = ref(false);

    let startPageX = 0;
    let startScrollLeft = 0;
    let pointerId: number | null = null;

    function endDrag(): void {
        isDragging.value = false;
        pointerId = null;

        const element = containerRef.value;

        if (element !== null) {
            element.classList.remove('cursor-grabbing', 'select-none');
            element.classList.add('cursor-grab');
        }
    }

    function onPointerDown(event: PointerEvent): void {
        const element = containerRef.value;

        if (element === null || event.button !== 0) {
            return;
        }

        pointerId = event.pointerId;
        isDragging.value = true;
        suppressNextClick.value = false;
        startPageX = event.pageX;
        startScrollLeft = element.scrollLeft;

        element.setPointerCapture(event.pointerId);
        element.classList.add('cursor-grabbing', 'select-none');
        element.classList.remove('cursor-grab');
    }

    function onPointerMove(event: PointerEvent): void {
        const element = containerRef.value;

        if (
            element === null
            || ! isDragging.value
            || pointerId !== event.pointerId
        ) {
            return;
        }

        const delta = event.pageX - startPageX;

        if (Math.abs(delta) >= dragThreshold) {
            suppressNextClick.value = true;
        }

        element.scrollLeft = startScrollLeft - delta;
    }

    function onPointerUp(event: PointerEvent): void {
        const element = containerRef.value;

        if (
            element === null
            || pointerId === null
            || event.pointerId !== pointerId
        ) {
            return;
        }

        if (element.hasPointerCapture(event.pointerId)) {
            element.releasePointerCapture(event.pointerId);
        }

        endDrag();
    }

    function handleSlotClick(callback: () => void): void {
        if (suppressNextClick.value) {
            suppressNextClick.value = false;

            return;
        }

        callback();
    }

    onMounted(() => {
        const element = containerRef.value;

        if (element === null) {
            return;
        }

        element.classList.add('cursor-grab');

        element.addEventListener('pointerdown', onPointerDown);
        element.addEventListener('pointermove', onPointerMove);
        element.addEventListener('pointerup', onPointerUp);
        element.addEventListener('pointercancel', onPointerUp);
    });

    onUnmounted(() => {
        const element = containerRef.value;

        if (element === null) {
            return;
        }

        element.removeEventListener('pointerdown', onPointerDown);
        element.removeEventListener('pointermove', onPointerMove);
        element.removeEventListener('pointerup', onPointerUp);
        element.removeEventListener('pointercancel', onPointerUp);
    });

    return {
        isDragging,
        handleSlotClick,
    };
}
