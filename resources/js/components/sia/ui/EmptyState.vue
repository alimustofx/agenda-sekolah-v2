<script setup>
defineProps({ title: String, description: String })
</script>

<template>
    <div class="empty">
        <h3>{{ title }}</h3>
        <p v-if="description">{{ description }}</p>
        <div class="row" style="justify-content: center"><slot /></div>
    </div>
</template>
