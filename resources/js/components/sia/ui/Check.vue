<script setup>
defineProps({ modelValue: Boolean, label: String, description: String })
const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <label class="check">
        <input type="checkbox" :checked="modelValue" @change="emit('update:modelValue', $event.target.checked)" />
        <span>
            {{ label }}
            <small v-if="description">{{ description }}</small>
        </span>
    </label>
</template>
