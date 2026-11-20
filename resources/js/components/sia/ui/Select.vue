<script setup>
import { useAttrs, useId } from 'vue'

defineOptions({ inheritAttrs: false })
const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    label: String,
    error: String,
    hint: String,
    options: { type: Array, default: () => [] }, // [{ value, label }]
    placeholder: { type: String, default: '' },
    status: String,
    message: String,
})
const emit = defineEmits(['update:modelValue'])
const attrs = useAttrs()
const id = attrs.id || useId()

function onChange(e) {
    const offset = props.placeholder ? 1 : 0
    const idx = e.target.selectedIndex - offset
    emit('update:modelValue', idx < 0 ? null : props.options[idx].value)
}
</script>

<template>
    <div class="field" :class="[attrs.class, status && `field--${status}`]">
        <label v-if="label" class="field__label" :for="id">{{ label }}</label>
        <select :id="id" class="input" :class="{ 'input--error': error }" @change="onChange">
            <option v-if="placeholder" value="" :selected="modelValue === null || modelValue === ''">{{ placeholder }}</option>
            <option v-for="o in options" :key="o.value" :value="o.value" :selected="String(o.value) === String(modelValue)">{{ o.label }}</option>
        </select>
        <p v-if="message" class="field__msg">{{ message }}</p>
        <p v-if="hint && !error && !message" class="field__hint">{{ hint }}</p>
        <p v-if="error" class="field__error" role="alert">{{ error }}</p>
    </div>
</template>
