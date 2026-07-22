<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    align: { type: String, default: 'right' },
    width: { type: String, default: 'w-56' },
    direction: { type: String, default: 'down' },
});

const open = ref(false);
const placement = ref('down');
const root = ref(null);
const trigger = ref(null);

const alignments = {
    right: 'right-0',
    left: 'left-0',
};

const placements = {
    down: 'top-full mt-2',
    up: 'bottom-full mb-2',
};

function resolvePlacement() {
    if (props.direction === 'up' || props.direction === 'down') {
        return props.direction;
    }

    const rect = trigger.value?.getBoundingClientRect();

    if (!rect) {
        return 'down';
    }

    const spaceBelow = window.innerHeight - rect.bottom;

    return spaceBelow < 260 ? 'up' : 'down';
}

async function toggle() {
    if (!open.value) {
        placement.value = resolvePlacement();
    }

    open.value = !open.value;
    await nextTick();
}

function close() {
    open.value = false;
}

function onClickOutside(event) {
    if (root.value && !root.value.contains(event.target)) {
        close();
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        close();
    }
}

onMounted(() => {
    document.addEventListener('click', onClickOutside);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="relative">
        <div ref="trigger" @click="toggle">
            <slot name="trigger" :open="open" />
        </div>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="open"
                class="dropdown-panel absolute z-40 p-1.5"
                :class="[alignments[props.align], placements[placement], props.width]"
                @click="close"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </div>
</template>
