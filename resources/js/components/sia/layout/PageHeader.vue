<script setup>
defineProps({ title: String, description: String })
</script>

<template>
    <header class="page-head">
        <div>
            <div v-if="$slots.crumbs" class="crumbs"><slot name="crumbs" /></div>
            <h1 class="page-head__title">{{ title }}</h1>
            <p v-if="description" class="page-head__desc">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="page-head__actions"><slot /></div>
    </header>
</template>
