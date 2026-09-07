import { onMounted, onUnmounted, ref, type Ref } from 'vue';

type DragScrollOptions = {
    dragThreshold?: number;
};

export function useDragScroll(
    containerRef: Ref<HTMLElement | null>,
    options: DragScrollOptions = {},
) {
    const dragThreshold = options.dragThreshold ?? 6;
    const isDragging = ref(false);

    let isPointerDown = false;
    let hasDragged = false;
    let suppressClick = false;
    let startPageX = 0;
    let startScrollLeft = 0;
    let pointerId: number | null = null;

    function resetDragState(): void {
        isPointerDown = false;
        hasDragged = false;
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

        isPointerDown = true;
        hasDragged = false;
        pointerId = event.pointerId;
        startPageX = event.pageX;
        startScrollLeft = element.scrollLeft;
    }

    function onPointerMove(event: PointerEvent): void {
        const element = containerRef.value;

        if (
            element === null
            || ! isPointerDown
            || pointerId !== event.pointerId
        ) {
            return;
        }

        const delta = event.pageX - startPageX;

        if (! hasDragged) {
            if (Math.abs(delta) < dragThreshold) {
                return;
            }

            hasDragged = true;
            isDragging.value = true;
            element.setPointerCapture(event.pointerId);
            element.classList.add('cursor-grabbing', 'select-none');
            element.classList.remove('cursor-grab');
        }

        event.preventDefault();
        element.scrollLeft = startScrollLeft - delta;
    }

    function onPointerUp(event: PointerEvent): void {
        const element = containerRef.value;

        if (
            element === null
            || ! isPointerDown
            || pointerId !== event.pointerId
        ) {
            return;
        }

        if (hasDragged && element.hasPointerCapture(event.pointerId)) {
            element.releasePointerCapture(event.pointerId);
            suppressClick = true;
        }

        resetDragState();
    }

    function onClickCapture(event: MouseEvent): void {
        if (! suppressClick) {
            return;
        }

        suppressClick = false;
        event.preventDefault();
        event.stopPropagation();
    }

    function handleSlotClick(callback: () => void): void {
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
        element.addEventListener('click', onClickCapture, true);
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
        element.removeEventListener('click', onClickCapture, true);
    });

    return {
        isDragging,
        handleSlotClick,
    };
}
