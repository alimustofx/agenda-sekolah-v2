<script setup>
defineProps({ variant: { type: String, default: 'neutral' } })
</script>

<template>
    <span class="badge" :class="variant !== 'neutral' && `badge--${variant}`"><slot /></span>
</template>
