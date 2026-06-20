<script setup>
defineProps({
    width: {
        type: String,
        default: '100%',
    },
    height: {
        type: String,
        default: '1rem',
    },
    radius: {
        type: String,
        default: '0.375rem',
    },
});
</script>

<template>
    <span
        class="block animate-pulse bg-gray-100 dark:bg-gray-800"
        :style="{ width, height, borderRadius: radius }"
    ></span>
</template>
