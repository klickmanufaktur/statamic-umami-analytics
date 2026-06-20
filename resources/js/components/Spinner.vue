<script setup>
defineProps({
    size: {
        type: String,
        default: '1.75rem',
    },
});
</script>

<template>
    <svg
        class="animate-spin"
        viewBox="0 0 24 24"
        fill="none"
        :style="{ width: size, height: size, willChange: 'transform' }"
        role="status"
        aria-label="Lädt"
    >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.2;" />
        <path d="M12 2 A 10 10 0 0 1 22 12" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>
</template>
