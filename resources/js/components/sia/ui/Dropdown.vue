<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const open = ref(false)
const root = ref(null)
const close = (e) => {
    if (!root.value?.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', close))
onBeforeUnmount(() => document.removeEventListener('click', close))
</script>

<template>
    <div ref="root" class="dropdown">
        <div @click="open = !open"><slot name="trigger" :open="open" /></div>
        <div v-if="open" class="dropdown__menu" role="menu" @click="open = false"><slot /></div>
    </div>
</template>
