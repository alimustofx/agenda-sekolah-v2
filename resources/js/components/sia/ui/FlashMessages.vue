<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const flash = computed(() => page.props.sia?.flash ?? {})
const visible = ref(true)
let timer
watch(
    flash,
    () => {
        visible.value = true
        clearTimeout(timer)
        timer = setTimeout(() => (visible.value = false), 6000)
    },
    { deep: true, immediate: true },
)
</script>

<template>
    <div v-if="visible && (flash.success || flash.error)" style="margin-bottom: 20px" role="status">
        <p v-if="flash.success" class="alert alert--success">{{ flash.success }}</p>
        <p v-if="flash.error" class="alert alert--error">{{ flash.error }}</p>
    </div>
</template>
